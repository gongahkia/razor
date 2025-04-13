<template>
  <div class="home">
    <h2>Welcome to Razor Password Manager</h2>
    <p>A secure way to store and manage your passwords.</p>
    <div class="actions">
      <router-link to="/register" class="btn">Get Started</router-link>
      <router-link to="/login" class="btn secondary">Login</router-link>
    </div>
    <div class="features">
      <div class="feature">
        <h3>Secure Storage</h3>
        <p>All your passwords are encrypted with AES-256 encryption.</p>
      </div>
      <div class="feature">
        <h3>Easy Access</h3>
        <p>Access your passwords from anywhere, anytime.</p>
      </div>
      <div class="feature">
        <h3>Privacy First</h3>
        <p>Your master key never leaves your device.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.home {
  text-align: center;
}

.actions {
  margin: 30px 0;
  display: flex;
  justify-content: center;
  gap: 20px;
}

.btn {
  padding: 12px 24px;
  background-color: #4CAF50;
  color: white;
  border-radius: 4px;
  text-decoration: none;
  font-weight: bold;
}

.btn.secondary {
  background-color: #f2f2f2;
  color: #333;
}

.features {
  display: flex;
  justify-content: space-between;
  margin-top: 50px;
  gap: 20px;
}

.feature {
  flex: 1;
  padding: 20px;
  border-radius: 8px;
  background-color: #f9f9f9;
}
</style>