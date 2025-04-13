import { auth } from '@/firebase/config';
import { 
  createUserWithEmailAndPassword, 
  signInWithEmailAndPassword,
  signOut,
  onAuthStateChanged
} from 'firebase/auth';
import { ref } from 'vue';

const user = ref(null);
const error = ref(null);
const loading = ref(false);

onAuthStateChanged(auth, (_user) => {
  if (_user) {
    user.value = _user;
  } else {
    user.value = null;
  }
});

const register = async (email, password) => {
  error.value = null;
  loading.value = true;
  
  try {
    const response = await createUserWithEmailAndPassword(auth, email, password);
    if (!response) {
      throw new Error('Could not complete registration');
    }
    user.value = response.user;
    return response.user;
  } catch (err) {
    error.value = err.message;
    throw err;
  } finally {
    loading.value = false;
  }
};

const login = async (email, password) => {
  error.value = null;
  loading.value = true;
  
  try {
    const response = await signInWithEmailAndPassword(auth, email, password);
    if (!response) {
      throw new Error('Could not complete login');
    }
    user.value = response.user;
    return response.user;
  } catch (err) {
    error.value = err.message;
    throw err;
  } finally {
    loading.value = false;
  }
};

const logout = async () => {
  error.value = null;
  
  try {
    await signOut(auth);
    user.value = null;
  } catch (err) {
    error.value = err.message;
    throw err;
  }
};

export { user, error, loading, register, login, logout };