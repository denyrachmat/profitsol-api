INSERT INTO DMS.dbo.dms_menu_mstr (id,menu_name,menu_desc,menu_parent,menu_url,menu_icon,menu_status,created_at,updated_at) VALUES 
('9999','Settings','Settings App','0','','settings_applications',2,NULL,NULL)
,('10000','User Setup','User Registration','9999','userset','person_add',2,NULL,NULL)
,('10001','Role Setup','Role Setup for user','9999','roleset','verified_user',2,NULL,NULL)
,('10002','Menu Setup','Add New Application to portal','9999','menuset','view_list',2,NULL,NULL)
,('3','Manage','You can managing approval, content, etc here.','0',NULL,'accessibility_new',NULL,NULL,'2020-05-14 13:14:20.347')
,('4','Upload Document','Upload your new document here','12','updoc','cloud_upload',NULL,NULL,'2020-05-14 13:09:24.383')
,('1','Library','List of document','0',NULL,'folder_special',NULL,NULL,'2020-03-27 02:27:30.260')
,('5','Approver Set','Setup approver for approve document','3','setapprv','supervised_user_circle',NULL,NULL,NULL)
,('6','Outstanding Approval','List of outstanding approval','1','apprvlist','remove_red_eye',NULL,NULL,NULL)
,('7','Document Approval Status','List of approval status by document','12','docstat','announcement',NULL,NULL,'2020-05-14 13:09:03.923')
;
INSERT INTO DMS.dbo.dms_menu_mstr (id,menu_name,menu_desc,menu_parent,menu_url,menu_icon,menu_status,created_at,updated_at) VALUES 
('8','Setup Content','Setup content for inserting to the document','3','setcontent','calendar_view_day',NULL,NULL,NULL)
,('9','Setup Approval Content','Setup which user will be asked to fill the content first','3','contentdoc','menu_book',NULL,NULL,'2020-04-24 09:38:30.960')
,('10','Approval Notification','List of approval you need to decide','1','apprvinbox','playlist_add_check',NULL,NULL,'2020-05-12 14:42:20.787')
,('11','Document Version','View History version of your document','1','docver','account_tree',NULL,NULL,NULL)
,('2','Document List','List of Dept Folder','1','doclist','assignment',NULL,NULL,NULL)
,('12','Document Management','Manage your document here','0',NULL,'menu_book',NULL,NULL,NULL)
,('13','Circular Technical Create','Create Circular Ten cover','14','circularten','book',NULL,NULL,'2020-05-19 14:22:54.147')
,('14','Custom App','Custom menu for suporting DMS','0',NULL,'extension',NULL,NULL,NULL)
,('15','Content to App','Mapping content to specific application','14','mappingapp','add_comment',NULL,NULL,NULL)
;