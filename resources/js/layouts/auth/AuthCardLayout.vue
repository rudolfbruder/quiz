<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10"
    >
        <!--
          The same soft green cast the landing page uses, so an auth screen
          reads as the same room. Decorative, behind everything, ignored by
          assistive technology.
        -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(50%_40%_at_50%_0%,color-mix(in_oklab,var(--primary)_12%,transparent),transparent_70%)]"
        />

        <div class="relative flex w-full max-w-md flex-col gap-6">
            <Link
                :href="home()"
                class="flex items-center gap-2 self-center rounded-sm font-medium"
            >
                <AppLogoIcon class="size-9 fill-current text-foreground" />
                <span class="sr-only">QUIZ AI</span>
            </Link>

            <Card>
                <CardHeader class="px-8 text-center sm:px-10">
                    <CardTitle class="text-xl">{{ title }}</CardTitle>
                    <CardDescription>
                        {{ description }}
                    </CardDescription>
                </CardHeader>
                <CardContent class="px-8 sm:px-10">
                    <slot />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
