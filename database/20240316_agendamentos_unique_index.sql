ALTER TABLE agendamentos
    ADD COLUMN status_reserva_unico VARCHAR(20) GENERATED ALWAYS AS (
        CASE
            WHEN status IN ('Pendente','Confirmado','Concluido','Indisponivel','Indisponível') THEN status
            ELSE NULL
        END
    ) STORED,
    ADD UNIQUE KEY ux_agendamentos_data_horario_status (data_horario, status_reserva_unico);
