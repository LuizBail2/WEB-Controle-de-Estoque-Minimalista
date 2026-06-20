<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nexo Estoque — atividade da equipe</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color:#0f172a;">

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f1f5f9; padding:24px 0;">
        <tr>
            <td align="center">

                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 3px rgba(15,23,42,0.08);">

                    {{-- Cabeçalho --}}
                    <tr>
                        <td style="background-color:#4f46e5; padding:24px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="font-size:18px; font-weight:700; color:#ffffff; letter-spacing:0.2px;">
                                        Nexo Estoque
                                    </td>
                                    <td align="right" style="font-size:12px; color:#c7d2fe; text-transform:uppercase; letter-spacing:0.8px;">
                                        Atividade da equipe
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    {{-- Resumo --}}
                    <tr>
                        <td style="padding:32px 32px 8px 32px;">
                            <p style="margin:0 0 4px 0; font-size:13px; color:#64748b; text-transform:uppercase; letter-spacing:0.6px;">
                                Notificação automática
                            </p>
                            <h1 style="margin:0; font-size:20px; line-height:1.4; font-weight:700; color:#0f172a;">
                                {{ $headline }}
                            </h1>
                        </td>
                    </tr>

                    @if($details)
                        {{-- E-mail RICO: movimentação --}}
                        <tr>
                            <td style="padding:16px 32px 8px 32px;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e2e8f0; border-radius:10px; overflow:hidden;">
                                    @foreach($details as $label => $value)
                                        @continue($value === null || $value === '' || str_starts_with($label, '__'))
                                        <tr>
                                            <td style="padding:12px 16px; background-color:{{ $loop->even ? '#f8fafc' : '#ffffff' }}; font-size:13px; color:#64748b; width:40%; border-bottom:1px solid #f1f5f9;">
                                                {{ $label }}
                                            </td>
                                            <td style="padding:12px 16px; background-color:{{ $loop->even ? '#f8fafc' : '#ffffff' }}; font-size:14px; color:#0f172a; font-weight:600; border-bottom:1px solid #f1f5f9;">
                                                {{ $value }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </table>
                            </td>
                        </tr>

                        @if(!empty($details['__pdf_anexo']))
                            <tr>
                                <td style="padding:8px 32px;">
                                    <p style="margin:0; padding:12px 16px; background-color:#eef2ff; border-radius:8px; font-size:13px; color:#4338ca;">
                                        📎 O comprovante em PDF desta movimentação está anexado a este e-mail.
                                    </p>
                                </td>
                            </tr>
                        @endif
                    @endif

                    {{-- Rodapé --}}
                    <tr>
                        <td style="padding:24px 32px 32px 32px;">
                            <hr style="border:none; border-top:1px solid #e2e8f0; margin:0 0 16px 0;">
                            <p style="margin:0; font-size:12px; color:#94a3b8; line-height:1.6;">
                                Você recebeu este e-mail porque é o administrador desta conta no Nexo Estoque.
                                As ações dos funcionários da sua equipe geram notificações automáticas.
                            </p>
                        </td>
                    </tr>

                </table>

                <p style="margin:16px 0 0 0; font-size:11px; color:#cbd5e1;">
                    Nexo Estoque · Controle de estoque
                </p>

            </td>
        </tr>
    </table>

</body>
</html>
