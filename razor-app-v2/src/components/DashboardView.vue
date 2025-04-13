<template>
  <div class="dashboard">
    <div v-if="!masterKey">
      <h2>Enter Your Master Key</h2>
      <form @submit.prevent="verifyMasterKey">
        <div class="form-group">
          <label for="masterKeyInput">Master Key:</label>
          <input 
            type="password" 
            id="masterKeyInput" 
            v-model="masterKeyInput" 
            required
          />
        </div>
        <div v-if="error" class="error">{{ error }}</div>
        <button type="submit">Unlock</button>
      </form>
    </div>
    
    <div v-else>
      <div class="dashboard-header">
        <h2>Your Passwords</h2>
        <button @click="showAddPasswordModal = true" class="add-btn">
          Add New Password
        </button>
      </div>
      
      <div v-if="loading" class="loading">
        Loading passwords...
      </div>
      
      <div v-else-if="passwords.length === 0" class="no-passwords">
        <p>You haven't saved any passwords yet.</p>
        <button @click="showAddPasswordModal = true">Add Your First Password</button>
      </div>
      
      <div v-else class="password-list">
        <div 
          v-for="password in passwords" 
          :key="password.id" 
          class="password-item"
        >
          <div class="password-info">
            <div class="website">{{ password.website }}</div>
            <div class="username">{{ password.username }}</div>
            <div class="password-field">
              <input 
                :type="password.visible ? 'text' : 'password'" 
                :value="password.password" 
                readonly
              />
              <button @click="togglePasswordVisibility(password)" class="icon-btn">
                {{ password.visible ? '👁️' : '👁️‍🗨️' }}
              </button>
              <button @click="copyToClipboard(password.password)" class="icon-btn">
                📋
              </button>
            </div>
          </div>
          <div class="password-actions">
            <button @click="editPassword(password)" class="edit-btn">Edit</button>
            <button @click="confirmDeletePassword(password)" class="delete-btn">Delete</button>
          </div>
        </div>
      </div>
      
      <!-- Add Password Modal -->
      <div v-if="showAddPasswordModal" class="modal">
        <div class="modal-content">
          <span class="close" @click="showAddPasswordModal = false">&times;</span>
          <h3>Add New Password</h3>
          <form @submit.prevent="handleAddPassword">
            <div class="form-group">
              <label for="website">Website:</label>
              <input type="text" id="website" v-model="newPassword.website" required />
            </div>
            <div class="form-group">
              <label for="username">Username:</label>
              <input type="text" id="username" v-model="newPassword.username" required />
            </div>
            <div class="form-group">
              <label for="password">Password:</label>
              <div class="password-input-group">
                <input 
                  :type="showNewPassword ? 'text' : 'password'" 
                  id="password" 
                  v-model="newPassword.password" 
                  required 
                />
                <button 
                  type="button" 
                  @click="showNewPassword = !showNewPassword" 
                  class="icon-btn"
                >
                  {{ showNewPassword ? '👁️' : '👁️‍🗨️' }}
                </button>
                <button type="button" @click="generatePassword" class="icon-btn">
                  🔄
                </button>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" @click="showAddPasswordModal = false">Cancel</button>
              <button type="submit">Save</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Edit Password Modal -->
      <div v-if="showEditPasswordModal" class="modal">
        <div class="modal-content">
          <span class="close" @click="showEditPasswordModal = false">&times;</span>
          <h3>Edit Password</h3>
          <form @submit.prevent="handleUpdatePassword">
            <div class="form-group">
              <label for="editWebsite">Website:</label>
              <input type="text" id="editWebsite" v-model="editingPassword.website" required />
            </div>
            <div class="form-group">
              <label for="editUsername">Username:</label>
              <input type="text" id="editUsername" v-model="editingPassword.username" required />
            </div>
            <div class="form-group">
              <label for="editPassword">Password:</label>
              <div class="password-input-group">
                <input 
                  :type="showEditPassword ? 'text' : 'password'" 
                  id="editPassword" 
                  v-model="editingPassword.password" 
                  required 
                />
                <button 
                  type="button" 
                  @click="showEditPassword = !showEditPassword" 
                  class="icon-btn"
                >
                  {{ showEditPassword ? '👁️' : '👁️‍🗨️' }}
                </button>
                <button type="button" @click="generateEditPassword" class="icon-btn">
                  🔄
                </button>
              </div>
            </div>
            <div class="form-actions">
              <button type="button" @click="showEditPasswordModal = false">Cancel</button>
              <button type="submit">Update</button>
            </div>
          </form>
        </div>
      </div>
      
      <!-- Delete Confirmation Modal -->
      <div v-if="showDeleteModal" class="modal">
        <div class="modal-content">
          <h3>Confirm Deletion</h3>
          <p>Are you sure you want to delete the password for {{ deletingPassword?.website }}?</p>
          <div class="form-actions">
            <button @click="showDeleteModal = false">Cancel</button>
            <button @click="handleDeletePassword" class="delete-btn">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { user } from '@/services/authService';
import { 
  getMasterKey, 
  setMasterKey 
} from '@/services/masterKeyService';
import { 
  addPassword, 
  getPasswords, 
  updatePassword, 
  deletePassword, 
  error as passwordError 
} from '@/services/passwordService';

// Master key handling
const masterKey = computed(() => getMasterKey());
const masterKeyInput = ref('');
const error = ref('');

// Password state
const passwords = ref([]);
const loading = ref(false);

// Modal state
const showAddPasswordModal = ref(false);
const showEditPasswordModal = ref(false);
const showDeleteModal = ref(false);
const showNewPassword = ref(false);
const showEditPassword = ref(false);

// Form state
const newPassword = ref({
  website: '',
  username: '',
  password: ''
});
const editingPassword = ref(null);
const deletingPassword = ref(null);

// Verify master key
const verifyMasterKey = () => {
  if (!masterKeyInput.value) {
    error.value = 'Please enter your master key';
    return;
  }
  
  // Set the master key
  setMasterKey(masterKeyInput.value);
  
  // Load passwords
  loadPasswords();
};

// Load passwords from database
const loadPasswords = async () => {
  if (!user.value || !masterKey.value) return;
  
  loading.value = true;
  error.value = '';
  
  try {
    const fetchedPasswords = await getPasswords(user.value.uid, masterKey.value);
    
    // Add visible property to each password for UI toggling
    passwords.value = fetchedPasswords.map(pwd => ({
      ...pwd,
      visible: false
    }));
  } catch (err) {
    console.error('Error loading passwords:', err);
    error.value = 'Failed to load passwords. Your master key might be incorrect.';
    setMasterKey(null); // Clear the master key if it's incorrect
  } finally {
    loading.value = false;
  }
};

// Toggle password visibility
const togglePasswordVisibility = (password) => {
  password.visible = !password.visible;
};

// Copy password to clipboard
const copyToClipboard = async (text) => {
  try {
    await navigator.clipboard.writeText(text);
    alert('Password copied to clipboard!');
  } catch (err) {
    console.error('Failed to copy password:', err);
  }
};

// Generate a random password
const generatePassword = () => {
  const length = 16;
  const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=<>?';
  let password = '';
  
  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * charset.length);
    password += charset[randomIndex];
  }
  
  newPassword.value.password = password;
  showNewPassword.value = true; // Show the generated password
};

// Generate a random password for editing
const generateEditPassword = () => {
  const length = 16;
  const charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-+=<>?';
  let password = '';
  
  for (let i = 0; i < length; i++) {
    const randomIndex = Math.floor(Math.random() * charset.length);
    password += charset[randomIndex];
  }
  
  editingPassword.value.password = password;
  showEditPassword.value = true; // Show the generated password
};

// Add a new password
const handleAddPassword = async () => {
  if (!user.value || !masterKey.value) return;
  
  try {
    await addPassword(user.value.uid, newPassword.value, masterKey.value);
    
    // Reset form and close modal
    newPassword.value = { website: '', username: '', password: '' };
    showAddPasswordModal.value = false;
    
    // Reload passwords
    loadPasswords();
  } catch (err) {
    console.error('Error adding password:', err);
    error.value = passwordError.value || 'Failed to add password';
  }
};

// Edit password
const editPassword = (password) => {
  editingPassword.value = { ...password };
  showEditPassword.value = false;
  showEditPasswordModal.value = true;
};

// Update password
const handleUpdatePassword = async () => {
  if (!user.value || !masterKey.value || !editingPassword.value) return;
  
  try {
    await updatePassword(
      user.value.uid, 
      editingPassword.value.id, 
      {
        website: editingPassword.value.website,
        username: editingPassword.value.username,
        password: editingPassword.value.password
      },
      masterKey.value
    );
    
    // Close modal and reload passwords
    showEditPasswordModal.value = false;
    loadPasswords();
  } catch (err) {
    console.error('Error updating password:', err);
    error.value = passwordError.value || 'Failed to update password';
  }
};

// Confirm delete password
const confirmDeletePassword = (password) => {
  deletingPassword.value = password;
  showDeleteModal.value = true;
};

// Delete password
const handleDeletePassword = async () => {
  if (!user.value || !deletingPassword.value) return;
  
  try {
    await deletePassword(user.value.uid, deletingPassword.value.id);
    
    // Close modal and reload passwords
    showDeleteModal.value = false;
    loadPasswords();
  } catch (err) {
    console.error('Error deleting password:', err);
    error.value = passwordError.value || 'Failed to delete password';
  }
};

// Load passwords when component mounts if master key is available
onMounted(() => {
  if (user.value && masterKey.value) {
    loadPasswords();
  }
});
</script>

<style scoped>
.dashboard {
  max-width: 800px;
  margin: 0 auto;
}

.dashboard-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 20px;
}

.add-btn {
  background-color: #4CAF50;
  color: white;
  border: none;
  padding: 10px 15px;
  border-radius: 4px;
  cursor: pointer;
}

.loading, .no-passwords {
  text-align: center;
  padding: 30px;
  background-color: #f9f9f9;
  border-radius: 8px;
}

.password-list {
  display: flex;
  flex-direction: column;
  gap: 15px;
}

.password-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 15px;
  background-color: #f9f9f9;
  border-radius: 8px;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.password-info {
  flex: 1;
}

.website {
  font-weight: bold;
  margin-bottom: 5px;
}

.username {
  color: #666;
  margin-bottom: 5px;
}

.password-field {
  display: flex;
  align-items: center;
  gap: 5px;
}

.password-field input {
  flex: 1;
  padding: 5px;
  border: 1px solid #ddd;
  border-radius: 4px;
  background-color: #f0f0f0;
}

.password-actions {
  display: flex;
  gap: 10px;
}

.icon-btn {
  background: none;
  border: none;
  cursor: pointer;
  font-size: 16px;
  padding: 5px;
}

.edit-btn, .delete-btn {
  padding: 8px 12px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.edit-btn {
  background-color: #2196F3;
  color: white;
}

.delete-btn {
  background-color: #f44336;
  color: white;
}

/* Modal styles */
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  width: 90%;
  max-width: 500px;
  position: relative;
}

.close {
  position: absolute;
  top: 10px;
  right: 15px;
  font-size: 24px;
  cursor: pointer;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
}

.form-group input {
  width: 100%;
  padding: 10px;
  border: 1px solid #ddd;
  border-radius: 4px;
}

.password-input-group {
  display: flex;
  gap: 5px;
}

.password-input-group input {
  flex: 1;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.form-actions button {
  padding: 10px 15px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.form-actions button:first-child {
  background-color: #f2f2f2;
  color: #333;
}

.form-actions button:last-child {
  background-color: #4CAF50;
  color: white;
}

.error {
  color: red;
  margin-bottom: 15px;
}
</style>