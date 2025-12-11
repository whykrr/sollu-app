<template>
  <Modal
    :class="{
      show: page.props.modal == 'modalDelete',
      hide: !activeModal != 'modalDelete',
    }"
    title="Modal Title"
    @close="closeModal"
  >
    <p class="text-gray-600">
      This is a simple modal. You can add any content here.
    </p>
    <template #footer>
      <button class="btn btn-main" @click="closeModal">Cancel</button>
      <button class="btn btn-main" @click="closeModal">Confirm</button>
    </template>
  </Modal>
</template>

<script setup>
import Modal from './Notifications/Modal.vue'

const closeModal = () => {
    activeModal.value = null
}
</script>
