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
