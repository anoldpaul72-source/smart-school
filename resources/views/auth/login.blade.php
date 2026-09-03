<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingia - School Results Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Outfit', sans-serif; }
        body {
            background: radial-gradient(circle at top right, #1e1b4b, #0f172a, #020617);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            color: #fff;
        }
        .login-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 2.5rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6);
        }
        .brand-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .brand-icon {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-radius: 18px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: #fff;
            margin-bottom: 1rem;
            box-shadow: 0 10px 20px rgba(79, 70, 229, 0.4);
        }
        .brand-title {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: -0.02em;
        }
        .brand-subtitle {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-top: 0.3rem;
        }
        .form-group {
            margin-bottom: 1.25rem;
        }
        .form-label {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            color: #cbd5e1;
        }
        .form-input {
            width: 100%;
            padding: 0.8rem 1.1rem;
            border-radius: 12px;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            font-size: 0.95rem;
            outline: none;
            transition: all 0.2s ease;
        }
        .form-input:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }
        .btn-submit {
            width: 100%;
            padding: 0.85rem;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #3b82f6);
            color: #fff;
            border: none;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);
            margin-top: 0.5rem;
        }
        .btn-submit:hover {
            opacity: 0.94;
            transform: translateY(-2px);
        }
        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            font-size: 0.88rem;
            margin-bottom: 1.25rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="brand-header">
        <div class="brand-icon">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
        <h1 class="brand-title">School Results Portal</h1>
        <p class="brand-subtitle">Ingia kwenye mfumo kutumia akaunti yako</p>
    </div>

    @if($errors->any())
    <div class="alert-error">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
    </div>
    @endif

    <form action="{{ route('login') }}" method="POST">
        @csrf
        <div class="form-group">
            <label class="form-label">Jina la Mtumiaji (Username)</label>
            <input type="text" name="username" class="form-input" placeholder="Weka username yako" value="{{ old('username') }}" required autofocus>
        </div>

        <div class="form-group">
            <label class="form-label">Nenosiri (Password)</label>
            <input type="password" name="password" class="form-input" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn-submit">
            Ingia Sasa <i class="fa-solid fa-arrow-right"></i>
        </button>
    </form>
</div>

</body>
</html>
