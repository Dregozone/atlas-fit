<x-mail::message>
# Sign-up burst

There have been {{ $recentUsers->count() }} sign-ups in the last {{ $windowMinutes }} minutes. Individual emails are paused until the burst calms down.

<x-mail::table>
| Name | Email | Time |
| :-- | :-- | :-- |
@foreach ($recentUsers as $recentUser)
| {{ $recentUser->name }} | {{ $recentUser->email }} | {{ $recentUser->created_at->setTimezone('Europe/London')->format('H:i') }} |
@endforeach
</x-mail::table>

**Total users:** {{ number_format($totalUsers) }}

{{ config('app.name') }}
</x-mail::message>
