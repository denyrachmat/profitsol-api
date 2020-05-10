INSERT INTO DMS.dbo.dms_menu_mstr (id,menu_name,menu_desc,menu_parent,menu_url,menu_icon,menu_status,created_at,updated_at) VALUES 
('9999','Settings','Settings App','0','','settings_applications',2,NULL,NULL)
,('10000','User Setup','User Registration','9999','userset','person_add',2,NULL,NULL)
,('10001','Role Setup','Role Setup for user','9999','roleset','verified_user',2,NULL,NULL)
,('10002','Menu Setup','Add New Application to portal','9999','menuset','view_list',2,NULL,NULL)
,('3','Manage','You can managing document in this menu','0',NULL,'accessibility_new',NULL,NULL,NULL)
,('4','Upload Document','Upload your new document here','3','updoc','cloud_upload',NULL,NULL,NULL)
,('1','Library','List of document','0',NULL,'folder_special',NULL,NULL,'2020-03-27 02:27:30.260')
,('5','Approver Set','Setup approver for approve document','3','setapprv','supervised_user_circle',NULL,NULL,NULL)
,('6','Approval List','List of outstanding approval','1','apprvlist','remove_red_eye',NULL,NULL,NULL)
,('7','Document Status','See list of your document approval status','1','docstat','announcement',NULL,NULL,NULL)
;
INSERT INTO DMS.dbo.dms_menu_mstr (id,menu_name,menu_desc,menu_parent,menu_url,menu_icon,menu_status,created_at,updated_at) VALUES 
('8','Setup Content','Setup content for inserting to the document','3','setcontent','calendar_view_day',NULL,NULL,NULL)
,('9','Setup Approval Content','Setup which user will be asked to fill the content first','3','contentdoc','menu_book',NULL,NULL,'2020-04-24 09:38:30.960')
,('10','Approval Inbox','List of approval you need to decide','1','apprvinbox','playlist_add_check',NULL,NULL,NULL)
,('2','Document List','List of Dept Folder','1','doclist','assignment',NULL,NULL,NULL)
;