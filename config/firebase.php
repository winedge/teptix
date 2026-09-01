<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Firebase Cloud Messaging (FCM)
    |
    */

    'server_key' => env('FIREBASE_SERVER_KEY', 'BLjeDOOdRFVXytexmx3TjJctbWO-jdWAI907ps0pq9I45z8L9GNZHiPaZCnGseMhkatl54Pt-E-UyXZB95m3cug'),

    'project_id' => env('FIREBASE_PROJECT_ID', 'teptix-app-62910'),

    'credentials' => [
        'type' => 'service_account',
        'project_id' => env('FIREBASE_PROJECT_ID', 'teptix-app-62910'),
        'private_key_id' => env('FIREBASE_PRIVATE_KEY_ID', 'b104493ac6be1a78ae6c614bd57a2fefb6dacf2a'),
        'private_key' => env('FIREBASE_PRIVATE_KEY', "-----BEGIN PRIVATE KEY-----\nMIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQC7cqcDAtTOw2jn\ndnn/tkFIaRyBMIEhDfrnwzwiUZkE5o1opq8+IADGzBytMlznHXHyN8PtDCNNNR2x\ntKcTh+quPt1MNf/GrpLM/gxZvM3moAkgu9v/eYg7eOcCSDleVRoDMPeC3v3YZ1Uj\nLTsxdau3iXmDII2MwY9EF5lFeSVJFu9ML1iyu28wGh0+C9NeTLMQ0d0QKIM8jLZN\nN0HB4qa581Wxgzi7ii7zg6RjRU7jflIttIBYGnBb5dAcWThxL/wbqiZo0eM9hBUp\nwexbaor22ZS0y0THhanH+9LR1ESMSDq8R2c/rTBLsNSSKVz9il3dXFOPVPoR0NWT\nC54gXIibAgMBAAECggEAHftcOFCTHlFGLcOXBu4tafoaC5XpCZL1X22saCbaDV0d\nIDCNFihDR4jtS0yjbHoauC3wQjGvUdGdosds8+RJBr7aYa6/bxWMOIqoChB3dJyD\naT9zFF1pi22usYBA4NRG2VzTMhUd0CfTB9HbxnD2WuB71Zv22B07n54Qr3qNmq9w\n2MJa0XTZc0D6Y3Rs8ZxFbfsiUMculLSs3XUDhjglM5gGtwKG/IDTYit4aCx/T+Nc\nMPIoRpx6SAcffo8Xt6L5+71Y2B9IAQGVSjIEiqH2UeS8EiZKLTz52qFBzE2Qdlvt\npSZtuvCdH/N9yn7FPiH2kOD0fn80HVHH7GnYpfCl6QKBgQDeFLdmzGV6kzvWfleF\nNcjLtok+hlr/dj2HURhRqMAISCyZDqoPGkErLHC52fvhYv5jrML0c7P+sNN2cG9C\nKFWg07Rxjbukioe4RddN0GbfxAxT4QF+AC7hKFF8CB8CMqYeKFcg9OMJxaXdYMiR\nUSKXbEWA5ki5vE7KPWCDesxOyQKBgQDYE8u4tXbErsiGgNK2dleBEZEPE08ZeEzf\nu7xyICb+CYPD4FLquBZhFKchGlqSdGgknVZgyApaQoyhA9RqV8g1VjbGorG46Qp+\n6kIzqHE81yuej3jVzBwVgB9CazY5NMJcPkP+tAngkPG7a9RwQ/xNhZQSjG7c/HT8\nJt6xfgeaQwKBgQCq6nqz7P70tTfKPnYRGXGuBo/dxP1nupJkAC+dOSiBtzpLmZOc\nB/K7zXn8Lx3BOrTZ9W4dQKb4jxJQBvw5iF1OZ2BqPFB7s/n8AhRP2OIzuOhb7cF+\nPZnWw3bi5EUpJd3fO+DChnXtYWXj2MbDwBPFQhjDuXejKp/g2hfbWLjAsQKBgFxQ\nQMX+COrIfXUxTlSTxJiox594MWABTF59l2nuoJoBuKCTkvkigrUhHLIAz1cokmGq\nMoAQlpOvQON9fl+cdzWKSsacNW95aHmGXZgyS4ahqsoII6i7lff9j91Rbo4MI0lD\ndMs5YcrmQdA/pjf8Y6s++V7fTnHtDZG4jYWpAVh/AoGAI50rdLW0h2wZ6+l30f/O\nDizzxx3gRZGePzKnw+6/nQXnwjOMiZmz8Kw78bxfakPg6rscOMycwOLr64mDI/4Z\nyA3kXYswnlZ0zCIq7s/ze9+zNsJJB31otcGPdtdLveAd9/xUmotLMXer/KYAWZ1O\n32316S69BAnQhz10aI6sXgk=\n-----END PRIVATE KEY-----\n"),
        'client_email' => env('FIREBASE_CLIENT_EMAIL', 'firebase-adminsdk-fbsvc@teptix-app-62910.iam.gserviceaccount.com'),
        'client_id' => env('FIREBASE_CLIENT_ID', '110647999143296373377'),
        'auth_uri' => 'https://accounts.google.com/o/oauth2/auth',
        'token_uri' => 'https://oauth2.googleapis.com/token',
        'auth_provider_x509_cert_url' => 'https://www.googleapis.com/oauth2/v1/certs',
        'client_x509_cert_url' => 'https://www.googleapis.com/robot/v1/metadata/x509/firebase-adminsdk-fbsvc%40teptix-app-62910.iam.gserviceaccount.com',
        'universe_domain' => 'googleapis.com'
    ],

    /*
    |--------------------------------------------------------------------------
    | FCM API Settings
    |--------------------------------------------------------------------------
    */
    'fcm' => [
        'url' => 'https://fcm.googleapis.com/fcm/send',
        'timeout' => 30,
        'batch_size' => 1000, // Max tokens per request
    ]
];
