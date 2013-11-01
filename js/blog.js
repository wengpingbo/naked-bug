var nakedBug = {
	//type: 0 -- previous, 1 -- next
	getPostLists : function(type) {
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
	},

	indexInitialize : function() {
		 document.getElementById("previous").onclick = 
		   function() {nakedBug.getPostLists(0);};
		 document.getElementById("next").onclick = 
		   function() {nakedBug.getPostLists(1);};
		 // load first 20 posts
		 $.get(
			   "utils.php",
			   {op : "alist", start : 0},
			   function(data) {
				  document.getElementById("post_toc").innerHTML(data);
			   }
		 );
	 },

	Initialize : function() {
		 //load top nav
		 $.get(
				"utils.php",
				{op : "gnav"},
				function(data) {
				  $("#nav_hook").append(data);
				}
		  );
		  //load footer
		  $.get(
				"utils.php",
				{op : "footer"},
				function(data) {
				  $("#footer_hook").append(data);
				}
		  );
		var page = document.getElementsByTagName("body");
		var pagetype = page.dataset.pagetype;
		//check current page type, index or article
		if(pagetype == "index") nakedBug.indexInitialize();
	  }
};

nakedBug.Initialize();
