Attribute VB_Name = "Module1"
Sub Button1_Click()

    Dim conn As ADODB.Connection
    Dim rst As ADODB.Recordset
    Dim cmd As ADODB.Command
    Dim intRow As Integer
    Dim strModelList As String
    
    intRow = 3
    strModelList = ""
    
    Do While True
        If Sheet1.Cells(intRow, 2) <> "" Then
            strModelList = strModelList & RTrim(Sheet1.Cells(intRow, 2)) & ","
        Else
            Exit Do
        End If
    
        intRow = intRow + 1
    Loop
    
    If strModelList <> "" Then
        strModelList = Left(strModelList, Len(strModelList) - 1)
    End If
    
    Set conn = New ADODB.Connection
    conn.ConnectionString = "Driver={SQL Server};Server=192.168.100.10;Database=VMI_SME;Uid=sa;Pwd=stx;"
    conn.Open ConnectionString
    
    Set rst = New ADODB.Recordset
    rst.ActiveConnection = conn
    
    rst.Open "EXEC DOWNLOAD_PA100_BOM '" & strModelList & "'"
    
    With Worksheets("PA100 BOM").Range("a2:z500")
        .ClearContents
        .CopyFromRecordset rst
    End With
    
    rst.Close
    Set rst = Nothing
    
    conn.Close
    Set conn = Nothing
    
    Worksheets("PA100 BOM").Activate

End Sub
