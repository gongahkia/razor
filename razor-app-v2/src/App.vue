<template>
  <div class="app">
    <header>
      <nav v-if="user">
        <h1>Razor Password Manager</h1>
        <div class="nav-links">
          <router-link to="/dashboard">Dashboard</router-link>
          <button @click="handleLogout">Logout</button>
        </div>
      </nav>
      <nav v-else>
        <h1>Razor Password Manager</h1>
        <div class="nav-links">
          <router-link to="/">Home</router-link>
          <router-link to="/login">Login</router-link>
          <router-link to="/register">Register</router-link>
        </div>
      </nav>
    </header>
    <main>
      <router-view />
    </main>
  </div>
</template>

<script setup>
import { user, logout } from '@/services/authService';
import { clearMasterKey } from '@/services/masterKeyService';
import { useRouter } from 'vue-router';

const router = useRouter();

const handleLogout = async () => {
  try {
    await logout();
    clearMasterKey();
    router.push('/login');
  } catch (error) {
    console.error('Logout error:', error);
  }
};
</script>

<style>
/* Basic styling */
.app {
  font-family: Arial, sans-serif;
  max-width: 1200px;
  margin: 0 auto;
  padding: 20px;
}

header {
  margin-bottom: 30px;
}

nav {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-bottom: 20px;
  border-bottom: 1px solid #e2e2e2;
}

.nav-links {
  display: flex;
  gap: 16px;
}

a, button {
  text-decoration: none;
  padding: 8px 16px;
  border-radius: 4px;
  color: #333;
  background-color: #f2f2f2;
  cursor: pointer;
  border: none;
  font-size: 16px;
}

a.router-link-active {
  background-color: #333;
  color: white;
}

main {
  padding: 20px 0;
}
</style>