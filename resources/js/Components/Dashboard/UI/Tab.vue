<template>
    <div class="tab">
        <ul role="tablist">
            <slot name="navigation" />
        </ul>
        <div class="tab-content">
            <slot></slot>
        </div>
    </div>
</template>
<script setup></script>
