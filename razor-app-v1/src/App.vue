<template>
  <div id="app">
    <h1>Razor Password Manager</h1>
    <div v-if="!loggedIn">
      <login-form @login="handleLogin" />
    </div>
    <div v-else>
      <password-list :passwords="passwords" />
      <password-form @add-password="addPassword" />
    </div>
  </div>
</template>

<script>
import LoginForm from './components/LoginForm.vue'
import PasswordList from './components/PasswordList.vue'
import PasswordForm from './components/PasswordForm.vue'

export default {
  name: 'App',
  components: {
    LoginForm,
    PasswordList,
    PasswordForm
  },
  data() {
    return {
      loggedIn: false,
      passwords: []
    }
  },
  methods: {
    handleLogin() {
      this.loggedIn = true
    },
    addPassword(password) {
      this.passwords.push(password)
    }
  }
}
</script>