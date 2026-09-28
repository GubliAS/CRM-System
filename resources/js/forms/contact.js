export function contactFormData(contact = null, selectedAccountId = null) {
    return {
        salutation: contact?.salutation ?? '',
        first_name: contact?.first_name ?? '',
        last_name: contact?.last_name ?? '',
        account_id: contact?.account_id
            ? String(contact.account_id)
            : selectedAccountId
              ? String(selectedAccountId)
              : '',
        title: contact?.title ?? '',
        department: contact?.department ?? '',
        phone: contact?.phone ?? '',
        mobile: contact?.mobile ?? '',
        home_phone: contact?.home_phone ?? '',
        other_phone: contact?.other_phone ?? '',
        email: contact?.email ?? '',
        fax: contact?.fax ?? '',
        reports_to_id: contact?.reports_to_id ? String(contact.reports_to_id) : '',
        assistant: contact?.assistant ?? '',
        assistant_phone: contact?.assistant_phone ?? '',
        mailing_street: contact?.mailing_street ?? '',
        mailing_city: contact?.mailing_city ?? '',
        mailing_state: contact?.mailing_state ?? '',
        mailing_postal_code: contact?.mailing_postal_code ?? '',
        mailing_country: contact?.mailing_country ?? '',
        other_street: contact?.other_street ?? '',
        other_city: contact?.other_city ?? '',
        other_state: contact?.other_state ?? '',
        other_postal_code: contact?.other_postal_code ?? '',
        other_country: contact?.other_country ?? '',
        lead_source: contact?.lead_source ?? '',
        birthdate: contact?.birthdate ? String(contact.birthdate).slice(0, 10) : '',
        description: contact?.description ?? '',
        owner_id: contact?.owner_id ? String(contact.owner_id) : '',
    };
}

export function contactLabel(contact) {
    return [contact.first_name, contact.last_name].filter(Boolean).join(' ');
}
