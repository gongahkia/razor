import { ref } from 'vue';

const masterKey = ref(null);

const setMasterKey = (key) => {
  masterKey.value = key;
};

const getMasterKey = () => {
  return masterKey.value;
};

const clearMasterKey = () => {
  masterKey.value = null;
};

export { masterKey, setMasterKey, getMasterKey, clearMasterKey };