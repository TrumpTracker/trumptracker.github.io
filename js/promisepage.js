var subreddit = "trumptracker";
var comments_limit = 200;

var comments = [];
var insertedreplies = [];

function renderComment(reddit, reply = false) {
    if (typeof reddit.data.link_id !== 'undefined') {
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
        if (reddit.data.author != "[deleted]") {
            comment += "<a target='_blank' class='author' href='https://reddit.com/u/" + reddit.data.author + "'>" + reddit.data.author + "</a><span class='score'> &#8226; ";
        } else {
            comment += reddit.data.author + "<span class='score'> &#8226; ";
        }
        if (reddit.data.score == 1) {
            comment += reddit.data.score + " point";
        } else {
            comment += reddit.data.score + " points";
        }
        comment += "</span> <span class='time'>" + moment.utc(reddit.data.created_utc * 1000).format('LL') + "</span>";
        if (reddit.data.edited != false) {
            comment += "*";
        }
        comment += " <a target='_blank' href='https://reddit.com/r/" + subreddit + "/comments/" + reddit.data.link_id.replace("t3_", "") + "/" + subreddit + "/" + reddit.data.id + "/'><i class='fa fa-share-alt' aria-hidden='true'></i></a> <hr>" + SnuOwnd.getParser().render(reddit.data.body.replace(/(<([^>]+)>)/ig, ""));
        if (reddit.data.archived != false) {
            comment += " <i class='fa fa-archive archive' aria-hidden='true'></i>";
        }
        comment += "</div></div>";
        return comment;
    } else {
        return false;
    }
}

function renderComments() {
    if (comments.length == 0) {
        $(".loader").hide();
        $("#reddit_comments").append("<h4>There are no comments yet</h4>");
    } else {
        comments.sort(function(a, b) {
            if (!a.stickied) {
                return -1;
            }
            if (a.archived) {
                return 1;
            }
            if (a.score > b.score) {
                return -1;
            }
            return 0;
        });
        $(comments).each(function(i, e) {
            if (e.replies != '') {
                loopReplies(e.replies, (i + 1));
            }
        });
        $(insertedreplies).each(function(i, e) {
            comments.splice(e[0] + i, 0, e[1]);
        });
        $(".loader").hide();
		if(comments.length != 1) { plural = 's' } else { plural = '' }
        if (comments.length > comments_limit) {
            $(".commentcount").html(comments.length + " comment" + plural + " (" + comments_limit + " shown)");
        } else {
            $(".commentcount").html(comments.length + " comment" + plural);
        }

        $(comments).each(function(i, e) {
            if (i >= comments_limit) {
                return false;
            }
            $(".panel-group").append(e.rendered_body);
        });
    }
}

function loopComments(reddit) {
    $.each(reddit, function(i, data) {
        $.each(data, function(i, d) {
            if (d.hasOwnProperty("children")) {
                $.each(d.children, function(i, e) {
                    if (!(e.data.author == "TrumpTracker" && (e.data.body.indexOf('This is an archived post.') !== false || e.data.body.indexOf('Please use the comments to discuss this promise/policy') !== false))) {
                        e.data.rendered_body = renderComment(e);
                        comments.push(e.data);
                    }
                });
            }
        });
    });
}

function loopReplies(replies, index) {
    if (typeof replies !== 'undefined') {
        $.each(replies.data.children, function(i, e) {
            e.data.rendered_body = renderComment(e, true);
            insertedreplies.push([index, e.data]);
            if (e.data.replies != '') {
                return loopReplies(e.data.replies, (index + i));
            } else {
                return true;
            }
        });
    }
}

if (document.location.href.indexOf("?reddit") !== -1) {
    document.location = 'https://redd.it/' + JSON.parse(redditid)[0];
}

window.addEventListener('load', function() {
    var count = JSON.parse(redditid).length;
    $(JSON.parse(redditid)).each(function(index, redditid) {
        $.get("https://www.reddit.com/r/" + subreddit + "/comments/" + redditid + ".json", function(res) {
            res.shift();
            loopComments(res);
            if (!--count) {
                renderComments();
            }
        }).fail(function() {
            $(".loader").hide();
            $(".noscript").show();
        });
    });
});