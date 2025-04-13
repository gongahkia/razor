<template>
  <form @submit.prevent="addPassword">
    <input v-model="website" type="text" placeholder="Website" required>
    <input v-model="username" type="text" placeholder="Username" required>
    <input v-model="password" type="password" placeholder="Password" required>
    <button type="submit" :disabled="isLoading">{{ isLoading ? 'Adding...' : 'Add Password' }}</button>
    <p v-if="error" class="error">{{ error }}</p>
    <p v-if="success" class="success">{{ success }}</p>
  </form>
</template>

<script>
import axios from 'axios';

export default {
  data() {
    return {
      website: '',
      username: '',
      password: '',
      isLoading: false,
      error: null,
      success: null
    }
  },
  methods: {
    async addPassword() {
      this.isLoading = true;
      this.error = null;
      this.success = null;

      try {
        const token = localStorage.getItem('token');
        if (!token) {
          this.error = 'You must be logged in to add passwords';
          this.isLoading = false;
          return;
        }
        const response = await axios.post('http://localhost/src/backend/index.php', {
          action: 'add_password',  
          website: this.website,
          username: this.username,
          password: this.password
        }, {
          headers: {
            'Authorization': `Bearer ${token}`
          }
        });

        if (response.data.success) {
          this.success = 'Password added successfully!';
          this.$emit('add-password', {
            id: response.data.password_id,
            website: this.website,
            username: this.username,
            password: this.password
          });
          this.website = '';
          this.username = '';
          this.password = '';
        } else {
          this.error = response.data.message || 'Failed to add password';
        }
      } catch (error) {
        console.error('Add password error:', error);
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
.success {
  color: green;
  margin-top: 10px;
}
</style>