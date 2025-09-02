<?php
require_once __DIR__ . '/wa.php';

/** Notify therapist about a new booking. */
function notifyTherapistNewBooking($bookingId)
{
    // TODO: Fetch booking + therapist phone from DB
    // Example SQL:
    // SELECT t.phone AS to_number, b.service, b.date_time
    // FROM bookings b JOIN therapists t ON ...
    // WHERE b.id = $bookingId;

    $to        = getenv('WA_DEFAULT_THERAPIST_NUMBER'); // fallback
    $service   = 'Serviço';                // from DB
    $dateTime  = '00/00/0000 00:00';       // formatted date/time
    $message   = "Novo agendamento: {$service} em {$dateTime} (ID {$bookingId}).";

    return wa_send_text($to, $message);
}

/** Notify patient about approval or refusal. */
function notifyPatientBookingStatus($bookingId, $approved)
{
    // TODO: Fetch patient phone, service, date/time from DB
    $to       = ''; // patient phone
    $service  = 'Serviço';
    $dateTime = '00/00/0000 00:00';

    if ($approved) {
        return wa_send_template(
            $to,
            'consulta_confirmacao',
            'pt_BR',
            [[
                'type'       => 'body',
                'parameters' => [
                    ['type' => 'text', 'text' => $service],
                    ['type' => 'text', 'text' => $dateTime],
                ],
            ]]
        );
    }

    return wa_send_template(
        $to,
        'consulta_recusa',
        'pt_BR',
        [[
            'type'       => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $service],
            ],
        ]]
    );
}

/** Reminder one day before the session. */
function notifyPatientReminder($bookingId)
{
    // TODO: Fetch patient phone, service, date/time from DB
    $to       = ''; // patient phone
    $service  = 'Serviço';
    $dateTime = '00/00/0000 00:00';

    return wa_send_template(
        $to,
        'consulta_lembrete',
        'pt_BR',
        [[
            'type'       => 'body',
            'parameters' => [
                ['type' => 'text', 'text' => $service],
                ['type' => 'text', 'text' => $dateTime],
            ],
        ]]
    );
}
