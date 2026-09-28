export function leadFormData(lead = null) {
    return {
        salutation: lead?.salutation ?? '',
        first_name: lead?.first_name ?? '',
        last_name: lead?.last_name ?? '',
        company: lead?.company ?? '',
        title: lead?.title ?? '',
        email: lead?.email ?? '',
        phone: lead?.phone ?? '',
        mobile: lead?.mobile ?? '',
        lead_status: lead?.lead_status ?? 'New',
        lead_source: lead?.lead_source ?? '',
        rating: lead?.rating ?? '',
        industry: lead?.industry ?? '',
        annual_revenue:
            lead?.annual_revenue === null || lead?.annual_revenue === undefined
                ? ''
                : String(lead.annual_revenue),
        number_of_employees:
            lead?.number_of_employees === null || lead?.number_of_employees === undefined
                ? ''
                : String(lead.number_of_employees),
        website: lead?.website ?? '',
        street: lead?.street ?? '',
        city: lead?.city ?? '',
        state: lead?.state ?? '',
        postal_code: lead?.postal_code ?? '',
        country: lead?.country ?? '',
        description: lead?.description ?? '',
        owner_id: lead?.owner_id ? String(lead.owner_id) : '',
    };
}
