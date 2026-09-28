export function opportunityFormData(opportunity = null, defaultStage = '') {
    return {
        name: opportunity?.name ?? '',
        account_id: opportunity?.account_id ? String(opportunity.account_id) : '',
        amount:
            opportunity?.amount === null || opportunity?.amount === undefined
                ? ''
                : String(opportunity.amount),
        close_date: opportunity?.close_date ? String(opportunity.close_date).slice(0, 10) : '',
        stage: opportunity?.stage ?? defaultStage,
        type: opportunity?.type ?? '',
        lead_source: opportunity?.lead_source ?? '',
        next_step: opportunity?.next_step ?? '',
        description: opportunity?.description ?? '',
    };
}
