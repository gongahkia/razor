<template>
  <form @submit.prevent="login">
    <input v-model="username" type="text" placeholder="Username" required>
    <input v-model="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
</template>

<script>
export default {
  data() {
    return {
      username: '',
      password: ''
    }
  },
  methods: {
    login() {
      // Implement login logic here
      this.$emit('login')
    }
  }
}
</script>