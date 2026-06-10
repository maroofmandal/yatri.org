<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>{{ url('/') }}</loc>
    <priority>1.0</priority>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>{{ url('/posts') }}</loc>
    <priority>0.8</priority>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>{{ url('/plan') }}</loc>
    <priority>0.8</priority>
    <changefreq>weekly</changefreq>
  </url>
  <url>
    <loc>{{ url('/explore/trips') }}</loc>
    <priority>0.7</priority>
    <changefreq>daily</changefreq>
  </url>
  <url>
    <loc>{{ url('/pricing') }}</loc>
    <priority>0.6</priority>
    <changefreq>monthly</changefreq>
  </url>
  <url>
    <loc>{{ url('/rankings') }}</loc>
    <priority>0.5</priority>
    <changefreq>weekly</changefreq>
  </url>
  @foreach($posts as $post)
  <url>
    <loc>{{ url('/posts/' . $post->slug) }}</loc>
    <lastmod>{{ $post->updated_at->toW3cString() }}</lastmod>
    <priority>0.7</priority>
    <changefreq>weekly</changefreq>
  </url>
  @endforeach
  @foreach($trips as $trip)
  <url>
    <loc>{{ url('/t/' . $trip->share_token) }}</loc>
    <lastmod>{{ $trip->updated_at->toW3cString() }}</lastmod>
    <priority>0.6</priority>
    <changefreq>weekly</changefreq>
  </url>
  @endforeach
  @foreach($users as $user)
  <url>
    <loc>{{ url('/u/' . $user->id) }}</loc>
    <lastmod>{{ $user->updated_at->toW3cString() }}</lastmod>
    <priority>0.4</priority>
    <changefreq>weekly</changefreq>
  </url>
  @endforeach
</urlset>
