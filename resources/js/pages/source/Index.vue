<script setup lang="ts">
import { Form, Head, useForm, usePoll } from '@inertiajs/vue3';
import { FileUp, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SourceController from '@/actions/App/Http/Controllers/Source/SourceController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { index as sourcesIndex } from '@/routes/sources';
import type { Source, SourceStatus, TopicSummary, UploadLimits } from '@/types';

const props = defineProps<{
    sources: Source[];
    topics: TopicSummary[];
    limits: UploadLimits;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Sources',
                href: sourcesIndex(),
            },
        ],
    },
});

const fileInput = ref<HTMLInputElement | null>(null);

const form = useForm<{ file: File | null }>({
    file: null,
});

function selectFile(event: Event): void {
    const input = event.target as HTMLInputElement;

    form.file = input.files?.[0] ?? null;
}

function upload(): void {
    form.post(SourceController.store.url(), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset('file');

            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
}

/**
 * A source that is still on its way to an answer, and the page should keep
 * looking. Everything else has settled and will not change on its own.
 */
const unsettled: SourceStatus[] = ['pending', 'processing'];

const isWaitingOnGeneration = computed(() =>
    props.sources.some((source) => unsettled.includes(source.status)),
);

// Only the uploads change while generation runs, so the poll asks for that prop
// alone and stops the moment every document has reached a final state.
const {
    start: startPolling,
    stop: stopPolling,
    polling,
} = usePoll(3000, { only: ['sources'] }, { autoStart: false });

watch(
    isWaitingOnGeneration,
    (waiting) => (waiting ? startPolling() : stopPolling()),
    { immediate: true },
);

/**
 * Nothing to show yet: one message saying what to do, rather than two empty
 * tables. A topic can outlive the documents it came from, so both listings have
 * to be empty before this is the whole story.
 */
const isEmpty = computed(
    () => props.sources.length === 0 && props.topics.length === 0,
);

const acceptedExtensions = computed(() =>
    props.limits.acceptedExtensions.map((extension) => `.${extension}`),
);

const maxUploadMegabytes = computed(() =>
    Math.round(props.limits.maxUploadKilobytes / 1024),
);

const statusLabels: Record<SourceStatus, string> = {
    pending: 'Waiting',
    processing: 'Analysing',
    ready: 'Ready',
    failed: 'Failed',
    rejected: 'Rejected',
};

const statusVariants: Record<
    SourceStatus,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    pending: 'outline',
    processing: 'secondary',
    ready: 'default',
    failed: 'destructive',
    rejected: 'destructive',
};

const mediaTypeLabels: Record<string, string> = {
    'application/pdf': 'PDF',
    'text/plain': 'Text',
    'text/markdown': 'Markdown',
};

function mediaTypeLabel(mediaType: string): string {
    return mediaTypeLabels[mediaType] ?? mediaType;
}

function formatSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function formatUploadedAt(uploadedAt: string | null): string {
    if (uploadedAt === null) {
        return '—';
    }

    return new Date(uploadedAt).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Sources" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <h1 class="sr-only">Sources</h1>

        <Card>
            <CardHeader>
                <CardTitle>Upload a document</CardTitle>
                <CardDescription>
                    Every document you upload is turned into a set of quiz
                    questions.
                </CardDescription>
            </CardHeader>

            <CardContent>
                <form class="space-y-4" @submit.prevent="upload">
                    <div class="grid gap-2">
                        <Label for="file">Document</Label>
                        <input
                            id="file"
                            ref="fileInput"
                            type="file"
                            name="file"
                            :accept="acceptedExtensions.join(',')"
                            class="h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-xs transition-[color,box-shadow] outline-none file:mr-3 file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                            @change="selectFile"
                        />
                        <InputError :message="form.errors.file" />
                        <p class="text-sm text-muted-foreground">
                            {{ acceptedExtensions.join(', ') }} up to
                            {{ maxUploadMegabytes }} MB and
                            {{ limits.maxPages }} pages.
                        </p>
                    </div>

                    <p
                        class="max-w-[70ch] text-sm leading-relaxed text-muted-foreground"
                    >
                        The document is sent to our AI provider, which reads it
                        and writes the questions. Do not upload anything you
                        would not want to leave this application.
                    </p>

                    <div
                        v-if="form.progress"
                        class="h-1.5 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        aria-label="Upload progress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-valuenow="form.progress.percentage"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-[width] duration-150"
                            :style="{
                                width: `${form.progress.percentage ?? 0}%`,
                            }"
                        />
                    </div>

                    <Button
                        type="submit"
                        :disabled="form.processing || form.file === null"
                        data-test="upload-source-button"
                    >
                        <FileUp class="size-4" />
                        {{ form.processing ? 'Uploading…' : 'Upload' }}
                    </Button>
                </form>
            </CardContent>
        </Card>

        <Card v-if="isEmpty">
            <CardHeader>
                <CardTitle>Nothing here yet</CardTitle>
                <CardDescription class="max-w-[70ch] leading-relaxed">
                    Upload your first document above. Once it has been read, its
                    questions become a topic you can study every day.
                </CardDescription>
            </CardHeader>
        </Card>

        <template v-else>
            <Card v-if="sources.length > 0" class="gap-0 overflow-hidden py-0">
                <CardHeader class="border-b pt-6">
                    <CardTitle>Your documents</CardTitle>
                    <CardDescription>
                        What you have uploaded and what came of it.
                    </CardDescription>

                    <CardAction v-if="polling">
                        <p
                            class="text-sm text-muted-foreground"
                            aria-live="polite"
                        >
                            Watching for updates…
                        </p>
                    </CardAction>
                </CardHeader>

                <CardContent class="overflow-x-auto px-0">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50 text-left">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Document
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Type
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Size
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Pages
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    State
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Quiz
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Uploaded
                                </th>
                                <th
                                    scope="col"
                                    class="px-4 py-3 text-right font-medium"
                                >
                                    <span class="sr-only">Actions</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="source in sources"
                                :key="source.id"
                                class="border-b transition-colors duration-150 last:border-b-0 hover:bg-muted/40"
                            >
                                <td class="px-4 py-3 align-top">
                                    <p class="font-medium">
                                        {{ source.originalName }}
                                    </p>
                                    <p
                                        v-if="source.failureReason"
                                        class="mt-1 max-w-md text-sm text-destructive"
                                    >
                                        {{ source.failureReason }}
                                    </p>
                                </td>
                                <td
                                    class="px-4 py-3 align-top text-muted-foreground"
                                >
                                    {{ mediaTypeLabel(source.mediaType) }}
                                </td>
                                <td
                                    class="px-4 py-3 align-top text-muted-foreground tabular-nums"
                                >
                                    {{ formatSize(source.sizeBytes) }}
                                </td>
                                <td
                                    class="px-4 py-3 align-top text-muted-foreground tabular-nums"
                                >
                                    {{ source.pageCount ?? '—' }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <Badge
                                        :variant="statusVariants[source.status]"
                                    >
                                        {{ statusLabels[source.status] }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <template v-if="source.quizTitle">
                                        <p>{{ source.quizTitle }}</p>
                                        <p class="text-muted-foreground">
                                            {{ source.questionCount }}
                                            {{
                                                source.questionCount === 1
                                                    ? 'question'
                                                    : 'questions'
                                            }}
                                        </p>
                                    </template>
                                    <span v-else class="text-muted-foreground"
                                        >—</span
                                    >
                                </td>
                                <td
                                    class="px-4 py-3 align-top whitespace-nowrap text-muted-foreground tabular-nums"
                                >
                                    {{ formatUploadedAt(source.uploadedAt) }}
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div
                                        class="flex items-center justify-end gap-1"
                                    >
                                        <Form
                                            v-if="source.status === 'failed'"
                                            v-bind="
                                                SourceController.retry.form(
                                                    source.id,
                                                )
                                            "
                                            :options="{ preserveScroll: true }"
                                            #default="{ processing }"
                                        >
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="sm"
                                                :disabled="processing"
                                                :data-test="`retry-source-${source.id}`"
                                            >
                                                <RotateCcw class="size-4" />
                                                <span class="sr-only"
                                                    >Try again</span
                                                >
                                            </Button>
                                        </Form>

                                        <Form
                                            v-if="
                                                source.status === 'failed' ||
                                                source.status === 'rejected'
                                            "
                                            v-bind="
                                                SourceController.destroy.form(
                                                    source.id,
                                                )
                                            "
                                            :options="{ preserveScroll: true }"
                                            #default="{ processing }"
                                        >
                                            <Button
                                                type="submit"
                                                variant="ghost"
                                                size="sm"
                                                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                :disabled="processing"
                                                :data-test="`delete-source-${source.id}`"
                                            >
                                                <Trash2 class="size-4" />
                                                <span class="sr-only"
                                                    >Delete</span
                                                >
                                            </Button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>

            <Card v-if="topics.length > 0" class="gap-0 overflow-hidden py-0">
                <CardHeader class="border-b pt-6">
                    <CardTitle>Your topics</CardTitle>
                    <CardDescription>
                        What your documents have added up to.
                    </CardDescription>
                </CardHeader>

                <CardContent class="overflow-x-auto px-0">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/50 text-left">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Topic
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Quizzes
                                </th>
                                <th scope="col" class="px-4 py-3 font-medium">
                                    Questions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="topic in topics"
                                :key="topic.id"
                                class="border-b transition-colors duration-150 last:border-b-0 hover:bg-muted/40"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ topic.name }}
                                </td>
                                <td
                                    class="px-4 py-3 text-muted-foreground tabular-nums"
                                >
                                    {{ topic.quizCount }}
                                </td>
                                <td
                                    class="px-4 py-3 text-muted-foreground tabular-nums"
                                >
                                    {{ topic.questionCount }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </template>
    </div>
</template>
