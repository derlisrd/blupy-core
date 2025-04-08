<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            padding-bottom: 20px;
            border-bottom: 2px solid #2c3e50;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .title {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .subtitle {
            font-size: 14px;
            color: #666;
        }
        .receipt-info {
            margin-top: 20px;
            padding: 10px 0;
            display: flex;
            justify-content: space-between;
        }
        .receipt-number, .receipt-date {
            font-size: 14px;
        }
        .details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #ddd;
        }
        .detail-label {
            font-weight: bold;
            width: 200px;
        }
        .amount {
            margin-top: 30px;
            text-align: right;
            font-size: 18px;
            font-weight: bold;
        }
        .amount span {
            border-bottom: 3px double #333;
            padding: 5px 15px;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .signature {
            margin-top: 70px;
            text-align: center;
        }
        .signature-line {
            width: 200px;
            margin: 0 auto;
            border-top: 1px solid #333;
            padding-top: 5px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">EMPRESA S.A.</div>
            <div class="title">RECIBO DE PAGO</div>
            <div class="subtitle">Comprobante de transacción</div>
        </div>

        <div class="receipt-info">
            <div class="receipt-number">
                <strong>RECIBO N°:</strong> {{ $numero }}
            </div>
            <div class="receipt-date">
                <strong>FECHA:</strong> {{ date('d/m/Y', strtotime($fecha)) }} - {{ $hora }}
            </div>
        </div>

        <div class="details">
            <div class="detail-row">
                <div class="detail-label">Número de Cuenta:</div>
                <div>{{ $cuenta }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Fecha de Transacción:</div>
                <div>{{ date('d/m/Y', strtotime($fecha)) }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Hora de Transacción:</div>
                <div>{{ $hora }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Número de Operación:</div>
                <div>{{ $numero }}</div>
            </div>
        </div>

        <div class="amount">
            <p>Monto Total: <span>$ {{ number_format($monto, 2, ',', '.') }}</span></p>
        </div>

        <div class="signature">
            <div class="signature-line">Firma Autorizada</div>
        </div>

        <div class="footer">
            <p>Este recibo es un comprobante de su transacción. Por favor, conserve este documento para futuras referencias.</p>
            <p>En caso de cualquier consulta, comuníquese con nuestro servicio de atención al cliente.</p>
            <p>Documento generado el {{ date('d/m/Y') }} a las {{ date('H:i:s') }}</p>
        </div>
    </div>
</body>
</html>