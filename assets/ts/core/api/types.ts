export type TimeType = 'instant' | 'interval';

export interface ApiTag {
    name: string;
    color: string;
}

export interface ApiStatistic {
    name: string;
    canonicalName: string;
    createdAt: string;
    createAtEpoch: number;
    color: string;
    unit: string;
    icon?: string;
}

export interface ApiStatisticValue {
    id: string;
    value: number;
    statistic: ApiStatistic;
}

export interface ApiTimeEntry {
    id: string;
    createdAt: string;
    updatedAt: string;
    updatedAtEpoch: number;
    startedAt: string;
    startedAtEpoch: number;
    endedAt?: string;
    endedAtEpoch?: number;
    description: string;
    duration?: string; // If the time entry is not over, no duration.
    taskId?: string;
    url?: string;
    tags: ApiTag[];
}

export interface ApiNote {
    id: string;
    title: string;
    content: string;
    createdAt: string;
    createAtEpoch: number;
    tags: ApiTag[];
    url?: string;
}

export interface ApiTimestamp {
    id: string;
    createdAt: string;
    createdAtEpoch: number;
    createdAgo?: string;
    tags: ApiTag[];
}

export interface ApiTask {
    id: string;
    name: string;
    description: string;
    createdAt: string;
    createdAtEpoch: number;
    completedAt?: string;
    completedAtEpoch?: number;
    url?: string;
    tags: ApiTag[];
}

export function ApiTagFromName(name: string): ApiTag {
    return {
        name,
        color: '#5d5d5d'
    };
}