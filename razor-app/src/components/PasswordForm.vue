<template>
  <form @submit.prevent="addPassword">
    <input v-model="website" type="text" placeholder="Website" required>
    <input v-model="username" type="text" placeholder="Username" required>
    <input v-model="password" type="password" placeholder="Password" required>
    <button type="submit">Add Password</button>
  </form>
</template>

<script>
export default {
  data() {
    return {
      website: '',
      username: '',
      password: ''
    }
  },
  methods: {
    addPassword() {
      this.$emit('add-password', {
        website: this.website,
        username: this.username,
        password: this.password
      })
      this.website = ''
      this.username = ''
      this.password = ''
    }
  }
}
</script>