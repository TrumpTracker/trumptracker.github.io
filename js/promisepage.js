function renderComment(reddit, reply = false) {
    if (reply) {
        comment = "<div class='panel panel-default replycomment'><div class='panel-body'><i class='fa reply fa-reply' aria-hidden='true'></i> ";
    } else {
        comment = "<div class='panel panel-default'><div class='panel-body'>";
    }
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
    comment += " <a target='_blank' href='https://reddit.com/r/trumptracker/comments/" + reddit.data.link_id.replace("t3_", "") + "/trumptracker/" + reddit.data.id + "/'><i class='fa fa-share-alt' aria-hidden='true'></i></a> <hr>" + SnuOwnd.getParser().render(reddit.data.body.replace(/(<([^>]+)>)/ig, ""));
    if (reddit.data.archived != false) {
        comment += " <i class='fa fa-archive archive' aria-hidden='true'></i>";
    }
    comment += "</div></div>";
    return comment;
}

var nocomments = true;

function loopComments(reddit, reply = false) {
    if (!reply) {
        $.each(reddit, function(i, data) {
            $.each(data, function(i, d) {
                if (d.hasOwnProperty("children")) {
                    $.each(d.children, function(i, e) {
                        nocomments = false;
                        $(".loader").hide();
                        $(".panel-group").append(renderComment(e));
                        if (e.data.replies != '') {
                            loopComments(e.data.replies, true);
                        }
                    });
                }
            });
        });
    } else {
        $.each(reddit.data.children, function(i, e) {
            $(".panel-group").append(renderComment(e, true));
            if (e.data.replies != '') {
                loopComments(e.data.replies, true);
            }
        });
    }
}

if (document.location.href.indexOf("?reddit") !== -1) {
    document.location = 'https://redd.it/' + redditid;
}

window.addEventListener('load', function() {
    $.get("http://www.reddit.com/r/trumptracker/comments/" + redditid + ".json", function(res) {
        res.shift();
        loopComments(res);
        setTimeout(function() {
            if (nocomments) {
                $(".loader").hide();
                $("#reddit_comments").append("<h4>There are no comments yet</h4>");
            }
        }, 500);
    }).fail(function() {
        $(".loader").hide();
        $(".noscript").show();
    });
});