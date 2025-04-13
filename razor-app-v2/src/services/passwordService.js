import { database } from '@/firebase/config';I
import { ref, set, get, remove, update, child } from 'firebase/database';
import { ref as vueRef } from 'vue';
import CryptoJS from 'crypto-js';

const error = vueRef(null);

const encryptPassword = (password, masterKey) => {
  return CryptoJS.AES.encrypt(password, masterKey).toString();
};

const decryptPassword = (encryptedPassword, masterKey) => {
  const bytes = CryptoJS.AES.decrypt(encryptedPassword, masterKey);
  return bytes.toString(CryptoJS.enc.Utf8);
};

const addPassword = async (userId, passwordData, masterKey) => {
  error.value = null;
  
  try {
    const newPasswordRef = ref(database, `passwords/${userId}/${Date.now()}`);
    const encryptedData = {
      ...passwordData,
      password: encryptPassword(passwordData.password, masterKey),
      createdAt: Date.now()
    };
    await set(newPasswordRef, encryptedData);
    return true;
  } catch (err) {
    error.value = err.message;
    throw err;
  }
};

const getPasswords = async (userId, masterKey) => {
  error.value = null;
  
  try {
    const passwordsRef = ref(database, `passwords/${userId}`);
    const snapshot = await get(passwordsRef);
    
    if (snapshot.exists()) {
      const passwords = [];
      snapshot.forEach((childSnapshot) => {
        const password = childSnapshot.val();
        passwords.push({
          id: childSnapshot.key,
          website: password.website,
          username: password.username,
          password: decryptPassword(password.password, masterKey),
          createdAt: password.createdAt
        });
      });
      return passwords;
    }
    return [];
  } catch (err) {
    error.value = err.message;
    throw err;
  }
};

const updatePassword = async (userId, passwordId, passwordData, masterKey) => {
  error.value = null;
  
  try {
    const passwordRef = ref(database, `passwords/${userId}/${passwordId}`);
    const encryptedData = {
      ...passwordData,
      password: encryptPassword(passwordData.password, masterKey),
      updatedAt: Date.now()
    };
    await update(passwordRef, encryptedData);
    return true;
  } catch (err) {
    error.value = err.message;
    throw err;
  }
};

const deletePassword = async (userId, passwordId) => {
  error.value = null;
  try {
    const passwordRef = ref(database, `passwords/${userId}/${passwordId}`);
    await remove(passwordRef);
    return true;
  } catch (err) {
    error.value = err.message;
    throw err;
  }
};

export { 
  error, 
  addPassword, 
  getPasswords, 
  updatePassword, 
  deletePassword,
  encryptPassword,
  decryptPassword
};