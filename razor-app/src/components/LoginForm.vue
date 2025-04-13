<template>
  <form @submit.prevent="login">
    <input v-model="username" type="text" placeholder="Username" required>
    <input v-model="password" type="password" placeholder="Password" required>
    <button type="submit" :disabled="isLoading">{{ isLoading ? 'Logging in...' : 'Login' }}</button>
    <p v-if="error" class="error">{{ error }}</p>
  </form>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      username: '',
      password: '',
      isLoading: false,
      error: null
    }
  },
  methods: {
    async login() {
      this.isLoading = true;
      this.error = null;

      try {
        const response = await axios.post('http://localhost/src/backend/index.php', {
          username: this.username,
          password: this.password
        });

        if (response.data.success) {
          localStorage.setItem('token', response.data.token);
          localStorage.setItem('user', JSON.stringify(response.data.user));
          this.$emit('login-success', response.data.user);
        } else {
          this.error = response.data.message || 'Login failed. Please try again.';
        }
      } catch (error) {
        console.error('Login error:', error);
        this.error = 'An error occurred. Please try again later.';
      } finally {
        this.isLoading = false;
      }
    }
  }
}
</script>

<style scoped>
.error {
  color: red;
  margin-top: 10px;
}
</style>