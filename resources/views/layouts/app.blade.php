<!doctype html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'A-Mazing Challenge 20')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Mono:wght@400;500&family=Manrope:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/glade-icons.css') }}">
    @stack('head')
</head>
<body>
<header class="topbar">
    <a class="brand" href="{{ route('assignments.index') }}"><span class="brand-mark">A</span><span>A-Mazing <b>20</b></span></a>
    <nav aria-label="Hoofdnavigatie">
        <a @class(['active' => request()->routeIs('assignments.*')]) href="{{ route('assignments.index') }}">Opdrachten</a>
        <a @class(['active' => request()->routeIs('submissions.*')]) href="{{ route('submissions.index') }}">Gemaakt</a>
        <a @class(['active' => request()->routeIs('glades.*')]) href="{{ route('glades.create') }}">Glade maken</a>
    </nav>
    <div class="team"><span>Zandbak</span><strong>Glade Runners</strong><i></i></div>
</header>

@if (session('success'))
    <div class="flash success">{{ session('success') }}</div>
@endif

<main>@yield('content')</main>

<footer><strong>A-Mazing Challenge 20</strong><span>Schooljaar 2026–2027 · HBO-ICT</span></footer>
@stack('scripts')
</body>
</html>
