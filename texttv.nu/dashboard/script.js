function initTexttv() {
  getLatestTextTvNews();

  // setInterval(getLatestTextTvNews, 10);
}

function getLatestTextTvNews() {
  $.getJSON("https://api.texttv.nu/api/get/100?app=dashboard.rasptouch").then(
    function(data) {
      if (data && data[0] && data[0].content) {
        // on page 100 some lines are not content (text tv logo)
        var content = data[0].content.join();
        var lines = content.split("\n");

        lines = lines.slice(0, 1).concat(lines.slice(5, 20));

        $(".texttv-latest-news").html(lines.join("\n"));
      }
    }
  );
}

function d(str) {
  var debugElm = $(".debug-output");
  var html = debugElm.html() + "\n\n" + str;
  debugElm.html(html);
}

initTexttv();
