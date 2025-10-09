document.addEventListener('DOMContentLoaded', function() {
    var wrapper = document.getElementById('yts-comments-wrapper');
    if (!wrapper) return;

    var videoId = wrapper.dataset.videoId;
    var apiKey = wrapper.dataset.apiKey;

    if (videoId && apiKey) {
        fetchComments(videoId, apiKey);
    }
});

function fetchComments(videoId, apiKey) {
    var url = 'https://www.googleapis.com/youtube/v3/commentThreads?part=snippet&videoId=' + videoId + '&key=' + apiKey + '&maxResults=20';

    fetch(url)
        .then(response => response.json())
        .then(data => {
            displayComments(data.items);
        })
        .catch(error => {
            document.getElementById('yts-comments-container').innerHTML = '<p>Error loading comments.</p>';
        });
}

function displayComments(comments) {
    var container = document.getElementById('yts-comments-container');
    var html = '<div class="yts-comments-list">';

    comments.forEach(function(comment) {
        var snippet = comment.snippet.topLevelComment.snippet;
        html += '<div class="yts-comment">';
        html += '<strong>' + snippet.authorDisplayName + '</strong><br>';
        html += '<p>' + snippet.textDisplay + '</p>';
        html += '</div>';
    });

    html += '</div>';
    container.innerHTML = html;
}
