<template>
  <ul>
    <li v-for="(password, index) in passwords" :key="index">
      {{ password.website }}: {{ password.username }}
    </li>
  </ul>
</template>

<script>
export default {
  props: ['passwords']
}
</script>