<template>
  <div class="register">
    <h2>Register</h2>
    <form @submit.prevent="handleRegister">
      <div class="form-group">
        <label for="email">Email:</label>
        <input 
          type="email" 
          id="email" 
          v-model="email" 
          required
        />
      </div>
      <div class="form-group">
        <label for="password">Password:</label>
        <input 
          type="password" 
          id="password" 
          v-model="password" 
          required
          minlength="8"
        />
      </div>
      <div class="form-group">
        <label for="confirmPassword">Confirm Password:</label>
        <input 
          type="password" 
          id="confirmPassword" 
          v-model="confirmPassword" 
          required
        />
      </div>
      <div v-if="passwordError" class="error">
        {{ passwordError }}
      </div>
      <div v-if="error" class="error">
        {{ error }}
      </div>
      <button type="submit" :disabled="loading">
        {{ loading ? 'Registering...' : 'Register' }}
      </button>
    </form>
    <p>Already have an account? <router-link to="/login">Login</router-link></p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import { register, error, loading } from '@/services/authService';
import { useRouter } from 'vue-router';

const router = useRouter();
const email = ref('');
const password = ref('');
const confirmPassword = ref('');
const passwordError = ref('');

const handleRegister = async () => {
  // Reset error
  passwordError.value = '';
  
  // Validate passwords match
  if (password.value !== confirmPassword.value) {
    passwordError.value = 'Passwords do not match';
    return;
  }
  
  try {
    await register(email.value, password.value);
    router.push('/setup-master-key');
  } catch (err) {
    console.error('Registration error:', err);
  }
};
</script>

<style scoped>
.register {
  max-width: 400px;
  margin: 0 auto;
}

form {
  display: flex;
  flex-direction: column;
  gap: 16px;
  margin-bottom: 20px;
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

button {
  padding: 12px;
  background-color: #4CAF50;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:disabled {
  background-color: #cccccc;
}

.error {
  color: red;
  margin-bottom: 10px;
}
</style>