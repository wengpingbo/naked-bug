var nakedBug = {
	//type: 0 -- previous, 1 -- next
	getPostLists : function(type) {
		var curpage = $("#page").data("curpage");
		if(type == 0 && curpage > 0) curpage--;
		if(type == 1 ) curpage++;
		page.dataset.curpage = curpage;
		$.get(
			  "utils.php",
			  //if the len is not 20, you should pass $len value in below
			  //param.
			  {op : "alist", start : (curpage+1)*20},
			  function(data) {
				  $("#post_toc").append(data);
			  }
		);
	},

	indexInitialize : function() {
		 $("#previous").click( 
		   function() {nakedBug.getPostLists(0);});
		 $("#next").click(
		   function() {nakedBug.getPostLists(1);});
		 // load first 20 posts
		 $.get(
			   "utils.php",
			   {op : "alist", start : 0},
			   function(data) {
				  $("#post_toc").append(data);
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
		//check current page type, index or article
		var pagetype = $("body").data("pagetype");
		if(pagetype == "index") nakedBug.indexInitialize();
	  }
};

nakedBug.Initialize();
