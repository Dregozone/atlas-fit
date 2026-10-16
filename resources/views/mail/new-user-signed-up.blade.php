<x-mail::message>
# New sign-up

<x-mail::table>
| | |
| :-- | :-- |
| **Name** | {{ $user->name }} |
| **Email** | {{ $user->email }} |
| **Time** | {{ $signedUpAtLondon }} |
| **Total users** | {{ number_format($totalUsers) }} |
@if ($referrer)
| **Referrer** | {{ $referrer }} |
@endif
@if ($country)
| **Country** | {{ $country }} |
@endif
</x-mail::table>

{{ config('app.name') }}
</x-mail::message>
