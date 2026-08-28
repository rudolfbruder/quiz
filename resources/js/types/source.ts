export type SourceStatus =
    'pending' | 'processing' | 'ready' | 'failed' | 'rejected';

export type Source = {
    id: number;
    originalName: string;
    mediaType: string;
    sizeBytes: number;
    status: SourceStatus;
    pageCount: number | null;
    failureReason: string | null;
    uploadedAt: string | null;
    quizTitle: string | null;
    questionCount: number | null;
};

export type TopicSummary = {
    id: number;
    name: string;
    slug: string;
    quizCount: number;
    questionCount: number;
};

export type UploadLimits = {
    acceptedExtensions: string[];
    acceptedMediaTypes: string[];
    maxUploadKilobytes: number;
    maxPages: number;
};
