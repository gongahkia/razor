<template>
  <div class="setup-master-key">
    <h2>Set Up Your Master Key</h2>
    <p class="info">
      Your master key is used to encrypt and decrypt your passwords. 
      It is never stored anywhere and cannot be recovered if lost.
    </p>
    
    <form @submit.prevent="handleSetupMasterKey">
      <div class="form-group">
        <label for="masterKey">Master Key:</label>
        <input 
          type="password" 
          id="masterKey" 
          v-model="masterKey" 
          required
          minlength="12"
        />
      </div>
      <div class="form-group">
        <label for="confirmMasterKey">Confirm Master Key:</label>
        <input 
          type="password" 
          id="confirmMasterKey" 
          v-model="confirmMasterKey" 
          required
        />
      </div>
      
      <div class="strength-meter">
        <div class="label">Password Strength:</div>
        <div class="meter">
          <div 
            class="strength" 
            :style="{ width: passwordStrength + '%', backgroundColor: strengthColor }"
          ></div>
        </div>
        <div class="strength-text">{{ strengthText }}</div>
      </div>
      
      <div v-if="error" class="error">
        {{ error }}
      </div>
      
      <button type="submit" :disabled="!isValidMasterKey">
        Continue to Dashboard
      </button>
    </form>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import { setMasterKey } from '@/services/masterKeyService';
import { useRouter } from 'vue-router';

const router = useRouter();
const masterKey = ref('');
const confirmMasterKey = ref('');
const error = ref('');
const passwordStrength = ref(0);

// Calculate password strength
watch(masterKey, (newValue) => {
  if (!newValue) {
    passwordStrength.value = 0;
    return;
  }
  
  let strength = 0;
  
  // Length check
  if (newValue.length >= 12) strength += 25;
  else if (newValue.length >= 8) strength += 15;
  else strength += 5;
  
  // Complexity checks
  if (/[A-Z]/.test(newValue)) strength += 20; // Has uppercase
  if (/[a-z]/.test(newValue)) strength += 15; // Has lowercase
  if (/[0-9]/.test(newValue)) strength += 20; // Has number
  if (/[^A-Za-z0-9]/.test(newValue)) strength += 20; // Has special char
  
  passwordStrength.value = Math.min(100, strength);
});

const strengthColor = computed(() => {
  if (passwordStrength.value < 30) return '#ff4d4d'; // Weak
  if (passwordStrength.value < 60) return '#ffa64d'; // Medium
  if (passwordStrength.value < 80) return '#ffff4d'; // Good
  return '#4CAF50'; // Strong
});

const strengthText = computed(() => {
  if (passwordStrength.value < 30) return 'Weak';
  if (passwordStrength.value < 60) return 'Medium';
  if (passwordStrength.value < 80) return 'Good';
  return 'Strong';
});

const isValidMasterKey = computed(() => {
  return masterKey.value.length >= 12 && passwordStrength.value >= 60;
});

const handleSetupMasterKey = () => {
  error.value = '';
  
  if (masterKey.value !== confirmMasterKey.value) {
    error.value = 'Master keys do not match';
    return;
  }
  
  if (passwordStrength.value < 60) {
    error.value = 'Please use a stronger master key';
    return;
  }
  
  // Set the master key in memory
  setMasterKey(masterKey.value);
  
  // Navigate to dashboard
  router.push('/dashboard');
};
</script>

<style scoped>
.setup-master-key {
  max-width: 500px;
  margin: 0 auto;
}

.info {
  background-color: #f8f9fa;
  padding: 15px;
  border-left: 4px solid #4CAF50;
  margin-bottom: 20px;
}

form {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

input {
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.strength-meter {
  margin-top: 10px;
}

.meter {
  height: 10px;
  background-color: #e0e0e0;
  border-radius: 5px;
  margin: 5px 0;
  overflow: hidden;
}

.strength {
  height: 100%;
  transition: width 0.3s, background-color 0.3s;
}

.strength-text {
  font-size: 14px;
  text-align: right;
}

button {
  padding: 12px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  margin-top: 10px;
}

button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}

.error {
  color: red;
  margin-bottom: 10px;
}
</style>