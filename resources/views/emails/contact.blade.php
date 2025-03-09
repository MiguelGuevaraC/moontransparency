<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo contacto interesado - Moon Group</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #003366;
            padding: 20px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            font-size: 24px;
            margin: 0;
        }
        .subheader {
            background-color: #e9ecef;
            padding: 10px 20px;
            border-bottom: 1px solid #dee2e6;
            text-align: center;
        }
        .content {
            padding: 25px;
        }
        .message {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            white-space: pre-line;
            border-left: 4px solid #003366;
        }
        .info-card {
            background-color: #e9f7fe;
            border-left: 4px solid #3498db;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
        }
        .label {
            font-weight: bold;
            color: #003366;
            margin-right: 5px;
        }
        .button {
            display: inline-block;
            background-color: #003366;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
        }
        .contact-info {
            margin-top: 25px;
            padding-top: 15px;
            border-top: 1px dashed #dee2e6;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>¡Nuevo contacto interesado!</h1>
        </div>
        
        <div class="subheader">
            <p>Una persona ha mostrado interés en Moon Group a través del Portal de Transparencia</p>
        </div>
        
        <div class="content">
            <div class="info-card">
                <p>Un visitante ha completado el formulario de contacto y está interesado en obtener más información sobre Moon Group.</p>
            </div>
            
            <p><span class="label">Asunto:</span> {{ $data['subject'] }}</p>
            <p><span class="label">Nombre:</span> {{ $data['name'] }}</p>
            <p><span class="label">Email:</span> {{ $data['email'] }}</p>
            
            
            <p class="label">Mensaje del interesado:</p>
            <div class="message">{{ $data['message'] }}</div>
            
            <div class="contact-info">
                <p class="label">Detalles adicionales:</p>
                <p>Fecha y hora: {{ date('d/m/Y H:i:s') }}</p>
                <p>Este mensaje fue enviado a través del Portal de Transparencia de Moon Group.</p>
                <p>Por favor, responda a la brevedad posible para mantener el interés del contacto.</p>
                
                <a href="mailto:{{ $data['email'] }}" class="button">Responder ahora</a>
            </div>
        </div>
        
        <div class="footer">
            <p>© {{ date('Y') }} Moon Group - Portal de Transparencia</p>
            <p>Este es un mensaje automático, por favor no responda a este correo.</p>
        </div>
    </div>
</body>
</html>