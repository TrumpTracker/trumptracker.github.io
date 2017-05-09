function renderComment(reddit, reply = false) {
	if(reply) {
		comment = "<div class='panel panel-default replycomment'><div class='panel-body'><i class='fa reply fa-reply' aria-hidden='true'></i> ";
	}
	else {
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
    comment += " <a target='_blank' href='https://reddit.com/r/trumptracker/comments/" + reddit.data.link_id.replace("t3_", "") + "/trumptracker/" + reddit.data.id + "/'><i class='fa fa-share-alt' aria-hidden='true'></i></a> <hr>" + SnuOwnd.getParser().render(reddit.data.body.replace(/(<([^>]+)>)/ig,""));
    if (reddit.data.archived != false) {
        comment += " <i class='fa fa-archive archive' aria-hidden='true'></i>";
    }
	comment += "</div></div>";
    return comment;
}

function loopComments(reddit, reply = false) {
    if (!reply) {
        $.each(reddit, function(i, data) {
            $.each(data, function(i, d) {
                $.each(d.children, function(i, e) {
                    $(".loader").hide();
                    $(".panel-group").append(renderComment(e));
                    if (e.data.replies != '') {
                        loopComments(e.data.replies, true);
                    }
                });
            });
        });
    } else {
        $.each(reddit.data.children, function(i, e) {
            $(".panel-group").append(renderComment(e,true));
            if (e.data.replies != '') {
                loopComments(e.data.replies, true);
            }
        });
    }
}

window.addEventListener('load', function() {
	reddit.comments(redditid, "trumptracker").limit(20).sort("hot").fetch(function(res) {
        res.shift();
        loopComments(res);
        if ($(".panel-group").html().indexOf('<div class="panel ')) {
            $(".loader").hide();
            $("#reddit_comments").append("<h4>There are no comments yet</h4>");
        }
    });
});

document.getElementsByClassName("loader")[0].style.display = 'block';
document.getElementsByClassName("noscript")[0].outerHTML='';