<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';

/**
 * The page is written as the questions a learner actually arrives with, answered
 * in order. The product's refusals are answers rather than a fine-print section,
 * which is where they persuade instead of deflate.
 */
const questions = [
    {
        ask: 'What do I give it?',
        answer: 'A PDF, a Word file, or a plain text or markdown note — something you already have. It is stored the moment you hand it over; nothing here makes you wait on a model.',
        facts: [
            { term: 'Accepts', value: 'PDF, DOCX, TXT, MD' },
            { term: 'Ceiling', value: '25 pages' },
            { term: 'Needs', value: 'Real text, no OCR' },
        ],
    },
    {
        ask: 'What does it do with it?',
        answer: 'It reads the document in the background and comes back with three candidate outlines — three different angles over the same material. You pick one, and only that one becomes a full question set, with its options and its explanations.',
        steps: [
            { name: 'Upload', body: 'The file is stored immediately.' },
            {
                name: 'Analyze',
                body: 'A background job reads it and proposes three outlines.',
            },
            {
                name: 'Choose',
                body: 'You pick the angle worth studying.',
            },
            { name: 'Serve', body: 'Five questions arrive each day.' },
            {
                name: 'Remind',
                body: 'A notification when a fresh quiz is waiting.',
            },
        ],
    },
];

const answers = [
    {
        ask: 'How much of my day?',
        answer: 'Five questions per topic, once a day. Anything you were asked in the last three days is held back, so you are not re-reading yesterday. Refreshing the page does not reshuffle the set — the same five are yours until tomorrow.',
    },
    {
        ask: 'Whose questions are these?',
        answer: 'Yours. Every question is generated from a file you uploaded. There is no shared question bank and no borrowed content, and you can always see which source a question came from.',
    },
    {
        ask: 'What if it cannot read my file?',
        answer: 'It says so. Over the page limit is refused with the real page count, never quietly cut short. An image-only scan is refused too — there is no OCR, so a document has to carry text of its own.',
    },
];
</script>

<template>
    <Head title="QUIZ AI" />

    <div class="relative min-h-screen bg-background text-foreground">
        <!--
          The one atmospheric moment: a single soft green cast behind the hero,
          reading as the light source the palette is named for. Decorative only,
          so it is hidden from assistive technology and sits behind everything.
        -->
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-[40rem] bg-[radial-gradient(60%_55%_at_50%_-5%,color-mix(in_oklab,var(--primary)_13%,transparent),transparent_70%)]"
        />

        <header
            class="sticky top-0 z-10 border-b border-border bg-background/80 backdrop-blur-sm"
        >
            <div
                class="mx-auto flex w-full max-w-5xl items-center justify-between px-6 py-4 lg:px-10"
            >
                <span class="text-sm font-semibold">QUIZ AI</span>

                <nav class="flex items-center gap-6 text-sm">
                    <Link
                        v-if="$page.props.auth.user"
                        :href="dashboard()"
                        class="rounded-sm text-muted-foreground transition-colors duration-150 hover:text-foreground"
                    >
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="rounded-sm text-muted-foreground transition-colors duration-150 hover:text-foreground"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-sm text-muted-foreground transition-colors duration-150 hover:text-foreground"
                        >
                            Register
                        </Link>
                    </template>
                </nav>
            </div>
        </header>

        <div class="mx-auto w-full max-w-5xl px-6 lg:px-10">
            <main>
                <section class="rise-in py-20 lg:py-28">
                    <h1
                        class="max-w-[16ch] text-5xl font-semibold tracking-tight text-balance sm:text-6xl lg:text-7xl"
                    >
                        Remember what you upload
                    </h1>

                    <p
                        class="mt-8 max-w-[62ch] text-base leading-relaxed text-muted-foreground sm:text-lg"
                    >
                        Hand over a document you already own. QUIZ AI reads it
                        and writes the questions. Then five of them come back
                        each day, and the ones you have just seen stay away for
                        three.
                    </p>

                    <Button as-child size="lg" class="mt-10">
                        <Link :href="register()">
                            Start a topic
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </section>

                <div class="space-y-6 pb-6">
                    <Card v-for="item in questions" :key="item.ask">
                        <CardHeader>
                            <CardTitle class="text-lg">
                                {{ item.ask }}
                            </CardTitle>
                        </CardHeader>

                        <CardContent class="space-y-6">
                            <p
                                class="max-w-[70ch] text-sm leading-relaxed text-muted-foreground"
                            >
                                {{ item.answer }}
                            </p>

                            <dl
                                v-if="item.facts"
                                class="grid gap-px overflow-hidden rounded-lg border border-border bg-border sm:grid-cols-3"
                            >
                                <div
                                    v-for="fact in item.facts"
                                    :key="fact.term"
                                    class="bg-muted px-4 py-3"
                                >
                                    <dt class="text-xs text-muted-foreground">
                                        {{ fact.term }}
                                    </dt>
                                    <dd
                                        class="mt-1 text-sm font-medium tabular-nums"
                                    >
                                        {{ fact.value }}
                                    </dd>
                                </div>
                            </dl>

                            <ol
                                v-if="item.steps"
                                class="divide-y divide-border overflow-hidden rounded-lg border border-border bg-muted"
                            >
                                <li
                                    v-for="(step, index) in item.steps"
                                    :key="step.name"
                                    class="flex flex-wrap gap-x-4 gap-y-1 px-4 py-3 text-sm"
                                >
                                    <span
                                        class="w-4 shrink-0 text-muted-foreground tabular-nums"
                                    >
                                        {{ index + 1 }}
                                    </span>
                                    <span class="font-medium">
                                        {{ step.name }}
                                    </span>
                                    <span class="text-muted-foreground">
                                        {{ step.body }}
                                    </span>
                                </li>
                            </ol>
                        </CardContent>
                    </Card>

                    <div class="grid gap-6 md:grid-cols-3">
                        <Card v-for="item in answers" :key="item.ask">
                            <CardHeader>
                                <CardTitle class="text-base">
                                    {{ item.ask }}
                                </CardTitle>
                            </CardHeader>

                            <CardContent>
                                <p
                                    class="text-sm leading-relaxed text-muted-foreground"
                                >
                                    {{ item.answer }}
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <section class="py-16 lg:py-20">
                    <Card>
                        <CardContent
                            class="flex flex-wrap items-center justify-between gap-8 py-4"
                        >
                            <h2
                                class="max-w-[24ch] text-2xl font-semibold text-balance"
                            >
                                One document. A minute a day.
                            </h2>

                            <Button as-child size="lg">
                                <Link :href="register()">
                                    Start a topic
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                </section>
            </main>

            <footer
                class="border-t border-border py-8 text-xs text-muted-foreground"
            >
                QUIZ AI
            </footer>
        </div>
    </div>
</template>
