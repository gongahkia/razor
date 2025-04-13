<?php

namespace Razor\Security;

class EncryptionService {
    /**
     * The encryption method to use
     * AES-256-GCM provides authenticated encryption
     */
    private string $method = 'aes-256-gcm';
    
    /**
     * Authentication tag length in bytes
     */
    private int $tagLength = 16;
    
    /**
     * Encrypt data using a key
     * 
     * @param string $data The data to encrypt
     * @param string $key The encryption key
     * @return array Associative array containing encrypted data, IV, and auth tag
     * @throws \Exception If encryption fails
     */
    public function encrypt(string $data, string $key): array {
        if (empty($data) || empty($key)) {
            throw new \InvalidArgumentException('Data and key cannot be empty');
        }
        
        // Generate a cryptographically secure initialization vector
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->method));
        if ($iv === false) {
            throw new \Exception('Failed to generate secure IV');
        }
        
        // Authentication tag will be populated by openssl_encrypt
        $tag = '';
        
        // Encrypt the data
        $encrypted = openssl_encrypt(
            $data,
            $this->method,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '', // Additional authentication data (AAD) - empty in this case
            $this->tagLength
        );
        
        if ($encrypted === false) {
            throw new \Exception('Encryption failed: ' . openssl_error_string());
        }
        
        // Return the encrypted data, IV, and authentication tag
        return [
            'data' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag)
        ];
    }
    
    /**
     * Decrypt data using a key
     * 
     * @param string $encryptedData Base64-encoded encrypted data
     * @param string $key The decryption key
     * @param string $iv Base64-encoded initialization vector
     * @param string $tag Base64-encoded authentication tag
     * @return string The decrypted data
     * @throws \Exception If decryption fails
     */
    public function decrypt(string $encryptedData, string $key, string $iv, string $tag): string {
        if (empty($encryptedData) || empty($key) || empty($iv) || empty($tag)) {
            throw new \InvalidArgumentException('Encrypted data, key, IV, and tag cannot be empty');
        }
        
        // Decode the base64-encoded data
        $decodedData = base64_decode($encryptedData);
        $decodedIv = base64_decode($iv);
        $decodedTag = base64_decode($tag);
        
        // Decrypt the data
        $decrypted = openssl_decrypt(
            $decodedData,
            $this->method,
            $key,
            OPENSSL_RAW_DATA,
            $decodedIv,
            $decodedTag
        );
        
        if ($decrypted === false) {
            throw new \Exception('Decryption failed: ' . openssl_error_string());
        }
        
        return $decrypted;
    }
    
    /**
     * Generate a secure encryption key
     * 
     * @param int $length Key length in bytes (default: 32 for AES-256)
     * @return string The generated key
     * @throws \Exception If key generation fails
     */
    public function generateKey(int $length = 32): string {
        $key = openssl_random_pseudo_bytes($length);
        
        if ($key === false) {
            throw new \Exception('Failed to generate secure key');
        }
        
        return base64_encode($key);
    }
    
    /**
     * Derive an encryption key from a password using PBKDF2
     * 
     * @param string $password The password to derive the key from
     * @param string $salt The salt to use (should be unique per user)
     * @param int $iterations Number of iterations (higher is more secure but slower)
     * @param int $keyLength Length of the derived key in bytes
     * @return string The derived key
     */
    public function deriveKeyFromPassword(
        string $password, 
        string $salt, 
        int $iterations = 100000, 
        int $keyLength = 32
    ): string {
        $derivedKey = hash_pbkdf2(
            'sha256',
            $password,
            $salt,
            $iterations,
            $keyLength,
            true
        );
        
        return base64_encode($derivedKey);
    }
    
    /**
     * Generate a cryptographically secure random salt
     * 
     * @param int $length Length of the salt in bytes
     * @return string Base64-encoded salt
     * @throws \Exception If salt generation fails
     */
    public function generateSalt(int $length = 16): string {
        $salt = random_bytes($length);
        
        if ($salt === false) {
            throw new \Exception('Failed to generate secure salt');
        }
        
        return base64_encode($salt);
    }
}
