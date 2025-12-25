<template>
  <Container>
    <div class="flex flex-col gap-4">
      <div>
        <h1 class="text-lg font-semibold">Basic Card</h1>
        <div class="grid grid-cols-6 gap-3">
          <Card
            v-for="product in products"
            :title="product.name"
            class=""
            image="https://dummyimage.com/200x200"
          >
            {{ product.description }}
            <template #footer>
              <div class="text-xs">
                <FontAwesomeIcon :icon="faBox" /> 20 Pcs
              </div>
              <div>
                <button class="btn btn-outline-main btn-sm">
                  Detail
                </button>
              </div>
            </template>
          </Card>
        </div>
      </div>

      <div>
        <h1 class="text-lg font-semibold">Card Fade</h1>
        <div class="grid grid-cols-6 gap-3">
          <CardFade
            v-for="product in products"
            :title="product.name"
            image="https://dummyimage.com/200x250"
          >
            {{ product.description }}
            <template #footer>
              <div class="text-xs">
                <FontAwesomeIcon :icon="faBox" /> 20 Pcs
              </div>
              <div class="space-x-1">
                <button
                  class="btn btn-highlight-success btn-sm rounded-full p-2!"
                >
                  <FontAwesomeIcon :icon="faPencil" />
                </button>
                <button
                  class="btn btn-highlight-main btn-sm rounded-full p-2!"
                >
                  <FontAwesomeIcon :icon="faSearch" />
                </button>
              </div>
            </template>
          </CardFade>
        </div>
      </div>
    </div>
  </Container>
</template>
<script setup>
import Card from '@/Components/UI/Card/Card.vue'
import CardFade from '@/Components/UI/Card/CardFade.vue'
import Container from '@/Components/UI/Container.vue'
import { faBox, faPencil, faSearch } from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

const products = [
    {
        name: 'Product',
        description: 'Product description',
        colors: 'main',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'secondary',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'danger',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'warning',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'info',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'info',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'info',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'info',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'info',
    },
    {
        name: 'Product',
        description: 'Product description',
        colors: 'info',
    },
]
</script>
