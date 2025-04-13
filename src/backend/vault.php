<?php

namespace Razor\Storage;

use Razor\Security\EncryptionService;
use Razor\Exception\VaultException;

class DataVault {
    /**
     * Encryption service instance
     */
    private EncryptionService $encryptionService;
    
    /**
     * Storage path for vault data
     */
    private string $storagePath;
    
    /**
     * Current user ID
     */
    private string $userId;
    
    /**
     * Master key for encryption/decryption
     */
    private ?string $masterKey = null;
    
    /**
     * In-memory cache of decrypted vault data
     */
    private array $cache = [];
    
    /**
     * Whether the vault has been modified since last save
     */
    private bool $modified = false;
    
    /**
     * Constructor
     * 
     * @param EncryptionService $encryptionService Encryption service instance
     * @param string $storagePath Path to store vault data
     * @param string $userId Current user ID
     */
    public function __construct(
        EncryptionService $encryptionService,
        string $storagePath,
        string $userId
    ) {
        $this->encryptionService = $encryptionService;
        $this->storagePath = rtrim($storagePath, '/');
        $this->userId = $userId;
        
        // Ensure storage directory exists
        if (!is_dir($this->storagePath)) {
            if (!mkdir($this->storagePath, 0750, true)) {
                throw new VaultException("Failed to create storage directory: {$this->storagePath}");
            }
        }
    }
    
    /**
     * Unlock the vault with the master key
     * 
     * @param string $masterKey Master key for encryption/decryption
     * @return bool True if vault was successfully unlocked
     * @throws VaultException If vault cannot be unlocked
     */
    public function unlock(string $masterKey): bool {
        $this->masterKey = $masterKey;
        
        // Try to load and decrypt the vault to verify the master key
        try {
            $this->load();
            return true;
        } catch (\Exception $e) {
            $this->masterKey = null;
            throw new VaultException("Failed to unlock vault: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Lock the vault
     * 
     * @param bool $save Whether to save changes before locking
     * @return bool True if vault was successfully locked
     * @throws VaultException If vault cannot be saved
     */
    public function lock(bool $save = true): bool {
        if ($save && $this->modified) {
            $this->save();
        }
        
        $this->masterKey = null;
        $this->cache = [];
        $this->modified = false;
        
        return true;
    }
    
    /**
     * Check if the vault is unlocked
     * 
     * @return bool True if vault is unlocked
     */
    public function isUnlocked(): bool {
        return $this->masterKey !== null;
    }
    
    /**
     * Get a value from the vault
     * 
     * @param string $key The key to get
     * @return mixed The value, or null if not found
     * @throws VaultException If vault is locked
     */
    public function get(string $key) {
        $this->ensureUnlocked();
        
        return $this->cache[$key] ?? null;
    }
    
    /**
     * Set a value in the vault
     * 
     * @param string $key The key to set
     * @param mixed $value The value to set
     * @return self
     * @throws VaultException If vault is locked
     */
    public function set(string $key, $value): self {
        $this->ensureUnlocked();
        
        $this->cache[$key] = $value;
        $this->modified = true;
        
        return $this;
    }
    
    /**
     * Delete a value from the vault
     * 
     * @param string $key The key to delete
     * @return bool True if the key existed and was deleted
     * @throws VaultException If vault is locked
     */
    public function delete(string $key): bool {
        $this->ensureUnlocked();
        
        if (isset($this->cache[$key])) {
            unset($this->cache[$key]);
            $this->modified = true;
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if a key exists in the vault
     * 
     * @param string $key The key to check
     * @return bool True if the key exists
     * @throws VaultException If vault is locked
     */
    public function has(string $key): bool {
        $this->ensureUnlocked();
        
        return isset($this->cache[$key]);
    }
    
    /**
     * Get all keys in the vault
     * 
     * @return array List of keys
     * @throws VaultException If vault is locked
     */
    public function getKeys(): array {
        $this->ensureUnlocked();
        
        return array_keys($this->cache);
    }
    
    /**
     * Get all values in the vault
     * 
     * @return array Associative array of key-value pairs
     * @throws VaultException If vault is locked
     */
    public function getAll(): array {
        $this->ensureUnlocked();
        
        return $this->cache;
    }
    
    /**
     * Save the vault to storage
     * 
     * @return bool True if vault was successfully saved
     * @throws VaultException If vault is locked or cannot be saved
     */
    public function save(): bool {
        $this->ensureUnlocked();
        
        // Prepare data for encryption
        $data = [
            'version' => '2.0.0',
            'created' => time(),
            'data' => $this->cache
        ];
        
        // Serialize and encrypt the data
        $serialized = json_encode($data);
        if ($serialized === false) {
            throw new VaultException("Failed to serialize vault data: " . json_last_error_msg());
        }
        
        try {
            $encrypted = $this->encryptionService->encrypt($serialized, $this->masterKey);
        } catch (\Exception $e) {
            throw new VaultException("Failed to encrypt vault data: " . $e->getMessage(), 0, $e);
        }
        
        // Add metadata
        $vaultData = [
            'id' => $this->userId,
            'version' => '2.0.0',
            'algorithm' => 'aes-256-gcm',
            'created' => time(),
            'encrypted' => $encrypted['data'],
            'iv' => $encrypted['iv'],
            'tag' => $encrypted['tag'],
            'checksum' => hash('sha256', $encrypted['data'])
        ];
        
        // Save to file
        $vaultPath = $this->getVaultPath();
        $json = json_encode($vaultData, JSON_PRETTY_PRINT);
        
        if ($json === false) {
            throw new VaultException("Failed to encode vault data: " . json_last_error_msg());
        }
        
        $tempFile = $vaultPath . '.tmp';
        $result = file_put_contents($tempFile, $json, LOCK_EX);
        
        if ($result === false) {
            throw new VaultException("Failed to write vault data to temporary file");
        }
        
        // Atomic rename to prevent data corruption
        if (!rename($tempFile, $vaultPath)) {
            unlink($tempFile);
            throw new VaultException("Failed to save vault data");
        }
        
        $this->modified = false;
        return true;
    }
    
    /**
     * Load the vault from storage
     * 
     * @return bool True if vault was successfully loaded
     * @throws VaultException If vault is locked or cannot be loaded
     */
    public function load(): bool {
        $this->ensureUnlocked();
        
        $vaultPath = $this->getVaultPath();
        
        if (!file_exists($vaultPath)) {
            // Initialize empty vault if it doesn't exist
            $this->cache = [];
            $this->modified = true;
            return true;
        }
        
        $json = file_get_contents($vaultPath);
        if ($json === false) {
            throw new VaultException("Failed to read vault data");
        }
        
        $vaultData = json_decode($json, true);
        if ($vaultData === null) {
            throw new VaultException("Failed to decode vault data: " . json_last_error_msg());
        }
        
        // Verify checksum
        $checksum = hash('sha256', $vaultData['encrypted']);
        if ($checksum !== $vaultData['checksum']) {
            throw new VaultException("Vault data integrity check failed");
        }
        
        try {
            $decrypted = $this->encryptionService->decrypt(
                $vaultData['encrypted'],
                $this->masterKey,
                $vaultData['iv'],
                $vaultData['tag']
            );
        } catch (\Exception $e) {
            throw new VaultException("Failed to decrypt vault data: " . $e->getMessage(), 0, $e);
        }
        
        $data = json_decode($decrypted, true);
        if ($data === null) {
            throw new VaultException("Failed to decode decrypted vault data: " . json_last_error_msg());
        }
        
        $this->cache = $data['data'];
        $this->modified = false;
        
        return true;
    }
    
    /**
     * Change the master key for the vault
     * 
     * @param string $newMasterKey The new master key
     * @return bool True if master key was successfully changed
     * @throws VaultException If vault is locked or master key cannot be changed
     */
    public function changeMasterKey(string $newMasterKey): bool {
        $this->ensureUnlocked();
        
        // Save with current key to ensure data is up to date
        if ($this->modified) {
            $this->save();
        }
        
        // Store current data
        $currentData = $this->cache;
        
        // Change the master key
        $oldMasterKey = $this->masterKey;
        $this->masterKey = $newMasterKey;
        
        try {
            // Save with new key
            $this->modified = true;
            $this->save();
            return true;
        } catch (\Exception $e) {
            // Restore old key if save fails
            $this->masterKey = $oldMasterKey;
            $this->cache = $currentData;
            throw new VaultException("Failed to change master key: " . $e->getMessage(), 0, $e);
        }
    }
    
    /**
     * Get the path to the vault file
     * 
     * @return string The vault file path
     */
    private function getVaultPath(): string {
        return $this->storagePath . '/' . $this->userId . '.vault';
    }
    
    /**
     * Ensure the vault is unlocked
     * 
     * @throws VaultException If vault is locked
     */
    private function ensureUnlocked(): void {
        if (!$this->isUnlocked()) {
            throw new VaultException("Vault is locked");
        }
    }
    
    /**
     * Destructor - automatically save changes if needed
     */
    public function __destruct() {
        if ($this->isUnlocked() && $this->modified) {
            try {
                $this->save();
            } catch (\Exception $e) {
                // Log error but don't throw from destructor
                error_log("Failed to save vault on destruct: " . $e->getMessage());
            }
        }
    }
}