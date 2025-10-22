# YouTube Suite Gallery Update

## What Changed

The gallery shortcode now supports showing **all posts** (both video posts and standard blog posts), not just video posts.

## File to Replace

Replace this file in your plugin:
- `includes/class-yts-gallery.php`

## How to Use

### Show All Posts (Videos + Standard Posts)
```
[youtube_gallery]
```
or
```
[youtube_gallery post_type="all"]
```

### Show Only Video Posts (Original Behavior)
```
[youtube_gallery post_type="videos"]
```

### Show Only Standard Posts (No Videos)
```
[youtube_gallery post_type="standard"]
```

### Combine with Other Parameters
```
[youtube_gallery post_type="all" columns="4" posts_per_page="16"]
[youtube_gallery post_type="standard" layout="list"]
[youtube_gallery post_type="videos" columns="3"]
```

## What's Different

**Before:**
- Gallery only showed posts with YouTube video IDs
- Standard blog posts were excluded
- Duration badges appeared on all items

**After:**
- Gallery can show all posts, only videos, or only standard posts
- Duration badges only appear on actual video posts
- Standard posts display normally without video metadata
- Backward compatible - existing shortcodes still work the same way

## Technical Details

The update:
1. Added a new `post_type` parameter to the shortcode (defaults to "all")
2. Conditionally applies meta_query based on the parameter:
   - `videos`: Only posts with `yt_video_id` meta
   - `standard`: Only posts WITHOUT `yt_video_id` meta  
   - `all`: No filtering (shows everything)
3. Only displays video duration badges on posts that have video data
4. Maintains all existing styling and layout options

## Example Use Cases

**Blog with mixed content:**
```
[youtube_gallery post_type="all"]
```
Shows both your YouTube videos and regular blog posts together.

**Separate galleries:**
```
<h2>Latest Videos</h2>
[youtube_gallery post_type="videos" posts_per_page="6"]

<h2>Blog Posts</h2>
[youtube_gallery post_type="standard" posts_per_page="6"]
```

**Video-only section (original behavior):**
```
[youtube_gallery post_type="videos"]
```
