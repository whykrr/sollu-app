<template>
  <PopUpPage
    :show="popUpStore.activePopUp.isVisible"
    :title="popUpStore.activePopUp.title"
    :sub-title="popUpStore.activePopUp.subTitle"
    :size="popUpStore.activePopUp.size"
    :component="popUpStore.activePopUp.component"
    :component-props="popUpStore.activePopUp.props"
    :component-events="popUpStore.activePopUp.events"
    @close="popUpStore.close()"
  />
</template>

<script setup>
import PopUpPage from '@/Components/UI/PopUpPage.vue'
import { usePopUpStore } from '@/store/popup'

const popUpStore = usePopUpStore()
</script>
