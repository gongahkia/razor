<?php

namespace Razor\Security;

class PasswordGenerator {
    /**
     * Character sets for password generation
     */
    private const CHARSET_LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const CHARSET_UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const CHARSET_NUMBERS = '0123456789';
    private const CHARSET_SYMBOLS = '!@#$%^&*()-_=+[]{};:,.<>?';
    private const CHARSET_SIMILAR = 'iIl1Lo0O';
    
    /**
     * Default password options
     */
    private array $options = [
        'length' => 16,
        'lowercase' => true,
        'uppercase' => true,
        'numbers' => true,
        'symbols' => true,
        'exclude_similar' => false,
        'exclude_ambiguous' => false,
    ];
    
    /**
     * Generate a random password
     * 
     * @param array $options Options to customize password generation
     * @return string The generated password
     * @throws \Exception If password generation fails
     */
    public function generate(array $options = []): string {
        // Merge provided options with defaults
        $options = array_merge($this->options, $options);
        
        // Build character set based on options
        $charset = $this->buildCharset($options);
        
        if (empty($charset)) {
            throw new \InvalidArgumentException('Character set is empty. Enable at least one character type.');
        }
        
        $password = '';
        $charsetLength = strlen($charset);
        
        // Generate random password
        for ($i = 0; $i < $options['length']; $i++) {
            $randomIndex = random_int(0, $charsetLength - 1);
            $password .= $charset[$randomIndex];
        }
        
        // Ensure password meets requirements
        if (!$this->validatePassword($password, $options)) {
            // Recursively try again if requirements not met
            return $this->generate($options);
        }
        
        return $password;
    }
    
    /**
     * Build character set based on options
     * 
     * @param array $options Options for character set
     * @return string The character set
     */
    private function buildCharset(array $options): string {
        $charset = '';
        
        if ($options['lowercase']) {
            $charset .= self::CHARSET_LOWERCASE;
        }
        
        if ($options['uppercase']) {
            $charset .= self::CHARSET_UPPERCASE;
        }
        
        if ($options['numbers']) {
            $charset .= self::CHARSET_NUMBERS;
        }
        
        if ($options['symbols']) {
            $charset .= self::CHARSET_SYMBOLS;
        }
        
        // Remove similar characters if requested
        if ($options['exclude_similar'] && !empty($charset)) {
            $charset = $this->removeCharacters($charset, self::CHARSET_SIMILAR);
        }
        
        // Remove ambiguous characters if requested
        if ($options['exclude_ambiguous'] && !empty($charset)) {
            $charset = str_replace(['{', '}', '[', ']', '(', ')', '/', '\\', '\'', '"', '`', '~', ',', ';', ':', '.', '<', '>'], '', $charset);
        }
        
        return $charset;
    }
    
    /**
     * Remove specified characters from a string
     * 
     * @param string $string The string to remove characters from
     * @param string $characters The characters to remove
     * @return string The string with characters removed
     */
    private function removeCharacters(string $string, string $characters): string {
        $result = $string;
        
        for ($i = 0; $i < strlen($characters); $i++) {
            $result = str_replace($characters[$i], '', $result);
        }
        
        return $result;
    }
    
    /**
     * Validate that a password meets requirements
     * 
     * @param string $password The password to validate
     * @param array $options The options to validate against
     * @return bool True if the password meets requirements, false otherwise
     */
    private function validatePassword(string $password, array $options): bool {
        // Check that password contains at least one character from each required set
        if ($options['lowercase'] && !preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        if ($options['uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        if ($options['numbers'] && !preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        if ($options['symbols'] && !preg_match('/[^a-zA-Z0-9]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Calculate password entropy (bits of randomness)
     * 
     * @param string $password The password to calculate entropy for
     * @return float The calculated entropy in bits
     */
    public function calculateEntropy(string $password): float {
        $length = strlen($password);
        
        if ($length === 0) {
            return 0;
        }
        
        // Count character types
        $hasLowercase = preg_match('/[a-z]/', $password);
        $hasUppercase = preg_match('/[A-Z]/', $password);
        $hasNumbers = preg_match('/[0-9]/', $password);
        $hasSymbols = preg_match('/[^a-zA-Z0-9]/', $password);
        
        // Calculate pool size
        $poolSize = 0;
        if ($hasLowercase) $poolSize += 26;
        if ($hasUppercase) $poolSize += 26;
        if ($hasNumbers) $poolSize += 10;
        if ($hasSymbols) $poolSize += 33; // Approximate number of common symbols
        
        // Calculate entropy using Shannon's formula: E = L * log2(R)
        // where L is password length and R is pool size
        return $length * log($poolSize, 2);
    }
}
