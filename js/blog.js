var nakedBug = {
	curpage : 0,
	maxpage : 0,
	//type: 0 -- previous, 1 -- next
	getPostLists : function(type) {
		//var curpage = $("#page").data("curpage");
		if(type == 0)
		{
		  if(nakedBug.curpage > 0) nakedBug.curpage -= 1;
		  else return;
		}
		if(type == 1 )
		{ 
		  if(nakedBug.maxpage == 0 || nakedBug.curpage < nakedBug.maxpage) 
			nakedBug.curpage += 1;
		  else return;
		}
		//$("#page").data("curpage", curpage);
		$.get(
			  "utils.php",
			  //if the len is not 20, you should pass $len value in below
			  //param.
			  {op : "alist", start : (nakedBug.curpage+1)*20},
			  function(data) {
				  if(data.length == 0 || data == "\n") nakedBug.maxpage = nakedBug.curpage;
				  else $("#post_toc").html(data);
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
				  if(data.length != 0 && data != "\n") $("#post_toc").html(data);
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
