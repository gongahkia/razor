<?php
declare(strict_types=1);

use Razor\Storage\PasswordRepository;
use Razor\Security\EncryptionService;
use Razor\Exception\AuditException;

class PasswordAudit
{
    private PasswordRepository $repository;
    private EncryptionService $encryptionService;
    private ?string $hibpApiKey;
    private array $breachCache = [];
    private int $minimumPasswordScore = 60;
    private int $maximumPasswordAgeDays = 365;

    public function __construct(
        PasswordRepository $repository,
        EncryptionService $encryptionService,
        ?string $hibpApiKey = null
    ) {
        $this->repository = $repository;
        $this->encryptionService = $encryptionService;
        $this->hibpApiKey = $hibpApiKey;
    }

    public function runFullAudit(int $userId): array
    {
        try {
            $passwords = $this->repository->getAllPasswords($userId);
            
            $result = [
                'timestamp' => time(),
                'total_passwords' => count($passwords),
                'weak_passwords' => $this->findWeakPasswords($passwords),
                'reused_passwords' => $this->findReusedPasswords($passwords),
                'old_passwords' => $this->findOldPasswords($passwords),
                'breached_passwords' => $this->findBreachedPasswords($passwords),
                'security_score' => 0,
                'risk_summary' => [],
                'recommendations' => []
            ];
            
            $result['security_score'] = $this->calculateSecurityScore($result);
            $result['risk_summary'] = $this->generateRiskSummary($result);
            $result['recommendations'] = $this->generateRecommendations($result);
            
            return $result;
        } catch (\Exception $e) {
            throw new AuditException("Failed to complete security audit: " . $e->getMessage(), 0, $e);
        }
    }

    private function findWeakPasswords(array $passwords): array
    {
        $weakPasswords = [];
        
        foreach ($passwords as $password) {
            $score = $this->evaluatePasswordStrength($password->getPassword());
            
            if ($score < $this->minimumPasswordScore) {
                $weakPasswords[] = [
                    'id' => $password->getId(),
                    'website' => $password->getWebsite(),
                    'username' => $password->getUsername(),
                    'strength_score' => $score,
                    'issues' => $this->getPasswordWeaknesses($password->getPassword())
                ];
            }
        }
        
        usort($weakPasswords, fn($a, $b) => $a['strength_score'] <=> $b['strength_score']);
        
        return $weakPasswords;
    }

    private function findReusedPasswords(array $passwords): array
    {
        $passwordMap = [];
        $reusedPasswords = [];
        
        foreach ($passwords as $password) {
            $passwordValue = $password->getPassword();
            $passwordHash = hash('sha256', $passwordValue);
            
            $passwordMap[$passwordHash][] = [
                'id' => $password->getId(),
                'website' => $password->getWebsite(),
                'username' => $password->getUsername()
            ];
        }
        
        foreach ($passwordMap as $hash => $entries) {
            if (count($entries) > 1) {
                $reusedPasswords[] = [
                    'password_hash' => substr($hash, 0, 8) . '...',
                    'count' => count($entries),
                    'entries' => $entries
                ];
            }
        }
        
        usort($reusedPasswords, fn($a, $b) => $b['count'] <=> $a['count']);
        
        return $reusedPasswords;
    }

    private function findOldPasswords(array $passwords): array
    {
        $oldPasswords = [];
        $currentTime = time();
        $maxAgeSeconds = $this->maximumPasswordAgeDays * 86400;
        
        foreach ($passwords as $password) {
            $lastChanged = $password->getLastModified() ?: $password->getCreated();
            $age = $currentTime - $lastChanged;
            
            if ($age > $maxAgeSeconds) {
                $oldPasswords[] = [
                    'id' => $password->getId(),
                    'website' => $password->getWebsite(),
                    'username' => $password->getUsername(),
                    'age_days' => floor($age / 86400),
                    'last_changed' => date('Y-m-d', $lastChanged)
                ];
            }
        }
        
        usort($oldPasswords, fn($a, $b) => $b['age_days'] <=> $a['age_days']);
        
        return $oldPasswords;
    }

    private function findBreachedPasswords(array $passwords): array
    {
        if ($this->hibpApiKey === null) {
            return [];
        }
        
        $breachedPasswords = [];
        $client = new \GuzzleHttp\Client(['timeout' => 10]);
        
        foreach ($passwords as $password) {
            $passwordValue = $password->getPassword();
            $passwordHash = strtoupper(sha1($passwordValue));
            $prefix = substr($passwordHash, 0, 5);
            $suffix = substr($passwordHash, 5);
            
            if (isset($this->breachCache[$passwordHash])) {
                $count = $this->breachCache[$passwordHash];
                if ($count > 0) {
                    $breachedPasswords[] = [
                        'id' => $password->getId(),
                        'website' => $password->getWebsite(),
                        'username' => $password->getUsername(),
                        'breach_count' => $count
                    ];
                }
                continue;
            }
            
            try {
                $response = $client->request('GET', "https://api.pwnedpasswords.com/range/{$prefix}", [
                    'headers' => ['hibp-api-key' => $this->hibpApiKey]
                ]);
                
                $body = (string) $response->getBody();
                $lines = explode("\n", $body);
                
                foreach ($lines as $line) {
                    [$hashSuffix, $count] = explode(":", trim($line));
                    $this->breachCache[strtoupper($prefix . $hashSuffix)] = (int) $count;
                    
                    if (strtoupper($hashSuffix) === $suffix && (int) $count > 0) {
                        $breachedPasswords[] = [
                            'id' => $password->getId(),
                            'website' => $password->getWebsite(),
                            'username' => $password->getUsername(),
                            'breach_count' => (int) $count
                        ];
                    }
                }
                
                usleep(1500000);
            } catch (\Exception $e) {
                error_log("Failed to check breach status: " . $e->getMessage());
            }
        }
        
        usort($breachedPasswords, fn($a, $b) => $b['breach_count'] <=> $a['breach_count']);
        
        return $breachedPasswords;
    }

    private function calculateSecurityScore(array $auditResults): int
    {
        $totalPasswords = $auditResults['total_passwords'];
        
        if ($totalPasswords === 0) {
            return 100;
        }
        
        $weakCount = count($auditResults['weak_passwords']);
        $reusedCount = array_sum(array_column($auditResults['reused_passwords'], 'count'));
        $oldCount = count($auditResults['old_passwords']);
        $breachedCount = count($auditResults['breached_passwords']);
        
        $weakPenalty = ($weakCount / $totalPasswords) * 40;
        $reusedPenalty = ($reusedCount / $totalPasswords) * 30;
        $oldPenalty = ($oldCount / $totalPasswords) * 15;
        $breachedPenalty = ($breachedCount / $totalPasswords) * 40;
        
        $score = 100 - ($weakPenalty + $reusedPenalty + $oldPenalty + $breachedPenalty);
        
        return max(0, min(100, (int) round($score)));
    }

    private function generateRiskSummary(array $auditResults): array
    {
        $totalPasswords = $auditResults['total_passwords'];
        
        if ($totalPasswords === 0) {
            return [
                'risk_level' => 'none',
                'message' => 'No passwords to evaluate.'
            ];
        }
        
        $weakCount = count($auditResults['weak_passwords']);
        $reusedGroups = count($auditResults['reused_passwords']);
        $oldCount = count($auditResults['old_passwords']);
        $breachedCount = count($auditResults['breached_passwords']);
        $score = $auditResults['security_score'];
        
        $summary = [
            'risk_level' => $this->getRiskLevel($score),
            'message' => $this->getRiskMessage($score),
            'issues' => []
        ];
        
        if ($weakCount > 0) {
            $percentage = round(($weakCount / $totalPasswords) * 100);
            $summary['issues'][] = "{$weakCount} weak passwords found ({$percentage}% of total).";
        }
        
        if ($reusedGroups > 0) {
            $summary['issues'][] = "{$reusedGroups} sets of reused passwords found.";
        }
        
        if ($oldCount > 0) {
            $percentage = round(($oldCount / $totalPasswords) * 100);
            $summary['issues'][] = "{$oldCount} passwords haven't been changed in over a year ({$percentage}% of total).";
        }
        
        if ($breachedCount > 0) {
            $percentage = round(($breachedCount / $totalPasswords) * 100);
            $summary['issues'][] = "{$breachedCount} passwords found in known data breaches ({$percentage}% of total).";
        }
        
        return $summary;
    }

    private function generateRecommendations(array $auditResults): array
    {
        $recommendations = [];
        
        if (!empty($auditResults['weak_passwords'])) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Strengthen weak passwords',
                'description' => 'Update weak passwords with stronger ones that include a mix of uppercase and lowercase letters, numbers, and special characters. Aim for at least 12 characters in length.'
            ];
        }
        
        if (!empty($auditResults['reused_passwords'])) {
            $recommendations[] = [
                'priority' => 'high',
                'title' => 'Eliminate password reuse',
                'description' => 'Using the same password across multiple sites is risky. If one site is compromised, all your accounts using that password are vulnerable. Create unique passwords for each site.'
            ];
        }
        
        if (!empty($auditResults['breached_passwords'])) {
            $recommendations[] = [
                'priority' => 'critical',
                'title' => 'Change compromised passwords immediately',
                'description' => 'These passwords have appeared in known data breaches and should be changed immediately. Choose strong, unique passwords for these accounts.'
            ];
        }
        
        if (!empty($auditResults['old_passwords'])) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Update old passwords',
                'description' => 'Passwords that haven\'t been changed in over a year should be updated, especially for important accounts.'
            ];
        }
        
        if ($auditResults['security_score'] < 90) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Enable two-factor authentication',
                'description' => 'For additional security, enable two-factor authentication (2FA) on all accounts that support it, especially for critical services like email, banking, and social media.'
            ];
        }
        
        if ($auditResults['security_score'] < 70) {
            $recommendations[] = [
                'priority' => 'medium',
                'title' => 'Use a password generator',
                'description' => 'Use the built-in password generator to create strong, random passwords instead of creating them yourself.'
            ];
        }
        
        usort($recommendations, function($a, $b) {
            $priorities = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
            return $priorities[$a['priority']] <=> $priorities[$b['priority']];
        });
        
        return $recommendations;
    }

    private function evaluatePasswordStrength(string $password): int
    {
        $score = 0;
        $length = strlen($password);
        
        $score += match (true) {
            $length >= 16 => 40,
            $length >= 12 => 30,
            $length >= 8 => 20,
            $length >= 6 => 10,
            default => 0
        };
        
        if (preg_match('/[a-z]/', $password)) $score += 10;
        if (preg_match('/[A-Z]/', $password)) $score += 10;
        if (preg_match('/[0-9]/', $password)) $score += 10;
        if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 10;
        
        if (preg_match('/^[a-zA-Z]+$/', $password)) $score -= 10;
        if (preg_match('/^[0-9]+$/', $password)) $score -= 20;
        if (preg_match('/(.)\1{2,}/', $password)) $score -= 10;
        
        if (preg_match('/password/i', $password)) $score -= 20;
        if (preg_match('/123456|qwerty|abc123/i', $password)) $score -= 20;
        
        return max(0, min(100, $score));
    }

    private function getPasswordWeaknesses(string $password): array
    {
        $weaknesses = [];
        $length = strlen($password);
        
        if ($length < 8) {
            $weaknesses[] = "Password is too short (minimum 8 characters recommended)";
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $weaknesses[] = "No lowercase letters";
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $weaknesses[] = "No uppercase letters";
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $weaknesses[] = "No numbers";
        }
        
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $weaknesses[] = "No special characters";
        }
        
        if (preg_match('/^[a-zA-Z]+$/', $password)) {
            $weaknesses[] = "Contains only letters";
        }
        
        if (preg_match('/^[0-9]+$/', $password)) {
            $weaknesses[] = "Contains only numbers";
        }
        
        if (preg_match('/(.)\1{2,}/', $password)) {
            $weaknesses[] = "Contains repeated characters";
        }
        
        if (preg_match('/password/i', $password)) {
            $weaknesses[] = "Contains common word 'password'";
        }
        
        if (preg_match('/123456|qwerty|abc123/i', $password)) {
            $weaknesses[] = "Contains common pattern";
        }
        
        return $weaknesses;
    }

    private function getRiskLevel(int $score): string
    {
        return match (true) {
            $score >= 90 => 'low',
            $score >= 70 => 'medium',
            $score >= 50 => 'high',
            default => 'critical'
        };
    }

    private function getRiskMessage(int $score): string
    {
        return match (true) {
            $score >= 90 => 'Your password security is excellent. Keep up the good work!',
            $score >= 70 => 'Your password security is good, but there is room for improvement.',
            $score >= 50 => 'Your password security needs attention. Please address the identified issues.',
            default => 'Your password security is at risk. Immediate action is recommended.'
        };
    }
}