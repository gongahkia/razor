<?php

namespace Razor\Security;

class PasswordHasher {
    /**
     * The hashing algorithm to use
     * As of PHP 8.2+, Argon2id is recommended for maximum security
     */
    private int $algorithm = PASSWORD_ARGON2ID;
    
    /**
     * Custom options for the hashing algorithm
     */
    private array $options = [
        'memory_cost' => 65536, // 64MB in KiB
        'time_cost' => 4,       // 4 iterations
        'threads' => 2          // 2 parallel threads
    ];
    
    /**
     * Hash a password
     * 
     * @param string $password The password to hash
     * @return string The hashed password
     * @throws \Exception If hashing fails
     */
    public function hashPassword(string $password): string {
        if (empty($password)) {
            throw new \InvalidArgumentException('Password cannot be empty');
        }
        
        $hash = password_hash($password, $this->algorithm, $this->options);
        
        if ($hash === false) {
            throw new \Exception('Password hashing failed');
        }
        
        return $hash;
    }
    
    /**
     * Verify a password against a hash
     * 
     * @param string $password The password to verify
     * @param string $hash The hash to verify against
     * @return bool True if the password matches the hash, false otherwise
     */
    public function verifyPassword(string $password, string $hash): bool {
        if (empty($password) || empty($hash)) {
            return false;
        }
        
        return password_verify($password, $hash);
    }
    
    /**
     * Check if a password needs to be rehashed
     * 
     * @param string $hash The hash to check
     * @return bool True if the hash needs to be rehashed, false otherwise
     */
    public function needsRehash(string $hash): bool {
        return password_needs_rehash($hash, $this->algorithm, $this->options);
    }
    
    /**
     * Set the hashing algorithm
     * 
     * @param int $algorithm The algorithm to use (PASSWORD_ARGON2ID, PASSWORD_BCRYPT, etc.)
     * @return self
     */
    public function setAlgorithm(int $algorithm): self {
        $this->algorithm = $algorithm;
        return $this;
    }
    
    /**
     * Set custom options for the hashing algorithm
     * 
     * @param array $options The options to set
     * @return self
     */
    public function setOptions(array $options): self {
        $this->options = array_merge($this->options, $options);
        return $this;
    }
    
    /**
     * Get information about a hash
     * 
     * @param string $hash The hash to get information about
     * @return array|false An array of information about the hash, or false on failure
     */
    public function getHashInfo(string $hash) {
        return password_get_info($hash);
    }
}
