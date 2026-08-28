<script setup lang="ts">
import { CircleCheck } from '@lucide/vue';
import { Alert, AlertDescription } from '@/components/ui/alert';

defineProps<{
    message: string;
}>();
</script>

<template>
    <Alert class="mb-4">
        <CircleCheck class="size-4" />
        <AlertDescription>{{ message }}</AlertDescription>
    </Alert>
</template>
