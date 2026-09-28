export function accountFormData(account = null) {
    return {
        name: account?.name ?? '',
        parent_account_id: account?.parent_account_id ? String(account.parent_account_id) : '',
        phone: account?.phone ?? '',
        fax: account?.fax ?? '',
        website: account?.website ?? '',
        type: account?.type ?? '',
        industry: account?.industry ?? '',
        employees:
            account?.employees === null || account?.employees === undefined
                ? ''
                : String(account.employees),
        annual_revenue:
            account?.annual_revenue === null || account?.annual_revenue === undefined
                ? ''
                : String(account.annual_revenue),
        billing_street: account?.billing_street ?? '',
        billing_city: account?.billing_city ?? '',
        billing_state: account?.billing_state ?? '',
        billing_postal_code: account?.billing_postal_code ?? '',
        billing_country: account?.billing_country ?? '',
        shipping_street: account?.shipping_street ?? '',
        shipping_city: account?.shipping_city ?? '',
        shipping_state: account?.shipping_state ?? '',
        shipping_postal_code: account?.shipping_postal_code ?? '',
        shipping_country: account?.shipping_country ?? '',
        description: account?.description ?? '',
        owner_id: account?.owner_id ? String(account.owner_id) : '',
    };
}
