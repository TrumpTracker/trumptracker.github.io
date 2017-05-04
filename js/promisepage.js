function renderComment(reddit) {
    comment = '';
    if (reddit.data.distinguished != null) {
        comment += "<i class='fa fa-check-square-o check' aria-hidden='true'></i> ";
    }
    if (reddit.data.stickied != false) {
        comment += "<i class='fa fa-thumb-tack check' aria-hidden='true'></i> ";
    }
    if (reddit.data.gilded > 0) {
        comment += "<i class='fa fa-trophy gild' aria-hidden='true'></i> ";
    }
    comment += "<a target='_blank' class='author' href='https://reddit.com/u/" + reddit.data.author + "'>" + reddit.data.author + "</a><span class='score'> &#8226; ";
    if (reddit.data.score == 1) {
        comment += reddit.data.score + " point";
    } else {
        comment += reddit.data.score + " points";
    }
    comment += "</span> <span class='time'>" + moment.utc(reddit.data.created_utc * 1000).format('LL') + "</span>";
    if (reddit.data.edited != false) {
        comment += "*";
    }
    comment += " <a target='_blank' href='https://reddit.com/r/trumptracker/comments/" + reddit.data.link_id.replace("t3_", "") + "/trumptracker/" + reddit.data.id + "/'><i class='fa fa-share-alt' aria-hidden='true'></i></a> <hr>" + SnuOwnd.getParser().render(reddit.data.body.replace(/(<([^>]+)>)/ig,""));
    if (reddit.data.archived != false) {
        comment += " <i class='fa fa-archive archive' aria-hidden='true'></i>";
    }
    return comment;
}

function loopComments(reddit, tree = true) {
    if (tree) {
        $.each(reddit, function(i, data) {
            $.each(data, function(i, d) {
                $.each(d.children, function(i, e) {
                    $(".loader").hide();
                    $("#reddit_comments").append("<div class='panel panel-default'><div class='panel-body'>" + renderComment(e) + "</div></div>");
                    if (e.data.replies != '') {
                        loopComments(e.data.replies, false);
                    }
                });
            });
        });
    } else {
        $.each(reddit.data.children, function(i, e) {
            $("#reddit_comments").append("<div class='panel panel-default' ><div class='panel-body'>" + renderComment(e) + "</div></div>");
            if (e.data.replies != '') {
                loopComments(e.data.replies, false);
            }
        });
    }
}

window.addEventListener('load', function() {
	reddit.comments(redditid, "trumptracker").limit(20).sort("hot").fetch(function(res) {
        res.shift();
        loopComments(res);
        if (!~$("#reddit_comments").html().indexOf('<div class="panel ')) {
            $(".loader").hide();
            $("#reddit_comments").append("<h4>There are no comments yet</h4>");
        }
    });
});
