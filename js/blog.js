function NakedBug() {
	//type: 0 -- previous, 1 -- next
	this.getPostLists = function(type) {
		var page = document.getElementById("page");
		var curpage = page.dataset.curpage;
		if(type == 0 && curpage > 0) curpage--;
		if(type == 1 ) curpage++;
		page.dataset.curpage = curpage;
		$.get(
			  "utils.php",
			  //if the len is not 20, you should pass $len value in below
			  //param.
			  {op : "alist", start : (curpage+1)*20},
			  function(data) {
				  document.getElementById("post_toc").innerHTML(data);
			  }
		);
	}

	 this.indexInitialize = function() {
		 document.getElementById("previous").onclick = this.getPostLists(0);
		 document.getElementById("next").onclick = this.getPostLists(1);
		 // load first 20 posts
		 $.get(
			   "utils.php",
			   {op : "alist", start : 0},
			   function(data) {
				  document.getElementById("post_toc").innerHTML(data);
			   }
		 );
	 }

	 (//common initialize...
	  function() {
		  //load top nav
		  $.get(
				"utils.php",
				{op : "gnav"},
				function(data) {
				  document.getElementById("nav_hook").innerHTML(data);
				}
		  );
		  //load footer
		  $.get(
				"utils.php",
				{op : "footer"},
				function(data) {
				  document.getElementById("footer_hook").innerHTML(data);
				}
		  );
	  }
	  )();
}

var nakedBug = new NakedBug();
