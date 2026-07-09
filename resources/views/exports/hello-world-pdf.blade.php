<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
            line-height: 1.6;
        }
        .header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #2c3e50;
            margin: 0;
            font-size: 28px;
        }
        .content {
            margin: 30px 0;
        }
        .message {
            background-color: #f8f9fa;
            padding: 20px;
            border-left: 4px solid #007bff;
            margin: 20px 0;
        }
        .info {
            background-color: #e9ecef;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
    </div>

    <div class="content">
        <div class="info">
            <strong>Date de génération:</strong> {{ $date }}
        </div>

        <div class="message">
            <h2>Message</h2>
            <p>{{ $message }}</p>
        </div>

        <h3>Informations techniques</h3>
        <ul>
            <li>Framework: Laravel 12</li>
            <li>Bibliothèque PDF: DomPDF</li>
            <li>Version PHP: {{ PHP_VERSION }}</li>
        </ul>
    </div>

    <div class="footer">
        <p>Document généré automatiquement par Edu-Manager</p>
    </div>
</body>
</html>
