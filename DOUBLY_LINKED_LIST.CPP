/* Q: Write a menu driven program that implements the following operations on a
      Doubly linked list :
	  -> Insert a new element at the beginning ,end and in-between the given list
	  -> Delete an existing element
	  -> Search an element
	  -> Display all the elements
*/
#include<iostream>
using namespace std;
struct node
{
	int data;
	node *next;
	node *prev;
}*save;
class DLL
{

public:
int a;
struct 	node *head,*tail;

void create_node();

void add_beg_node();
void add_mid_node();
void add_end_node();
void delete_beg();
void delete_mid();
void delete_end();
void display();
void search();
~DLL()
{
 delete head;
 delete tail;
 delete save;
}
};
void DLL :: create_node()
{
	a++;
	int i;
	cout<<"enter the data : ";
	cin>>i;
	node *temp=new node;
	temp->data=i;
	temp->next=NULL;
	if(head==NULL)
	{
	   	temp->prev=NULL;
	    head=temp;
	}
	else
	{
		save=head;
		while(save->next!=NULL)
		{
			save=save->next;
		}
		save->next=temp;
		temp->prev=save;
	}
}
void DLL :: add_beg_node()
{
	int i;
	if(head==NULL)
	{
		create_node();
	}
	else
	{
			a++;
	cout<<"enter the data : ";
	cin>>i;
	node *temp=new node;
	temp->data=i;
	temp->prev=NULL;
	temp->next=head;
	head->prev=temp;
	head=temp;
	}
}
void DLL ::add_mid_node()
{
	if(head==NULL)
	{
		create_node();
	}
	a++;
	int i,n;
	cout<<"enter the data : ";
	cin>>i;
    node *temp=new node;
	node *temp1=new node;
	temp->data=i;
	temp->next=NULL;
	cout<<"enter the node after which u want to insert : ";
	cin>>n;
	temp1=head;
    while(temp1->data!=n)
    {
      temp1=temp1->next;
    }
	  temp->next=temp1->next;
	  temp1->next=temp;
	  temp->prev=temp1;
    temp1->next->prev=temp;
  }
void DLL::add_end_node()
{
	if(head==NULL)
	{
	   create_node();
	}
	else
	{
		int i;
	   cout<<"enter the data : ";
	   cin>>i;
	   node *temp=new node;
	   temp->data=i;
	   temp->next=NULL;
		a++;
		save=head;
		while(save->next!=NULL)
		{
			save=save->next;
		}
		save->next=temp;
		temp->prev=save;
	}
}
void DLL ::delete_beg()
{
	a--;
	node *temp=new node;
	 if(head==NULL)
        {
            cout<<"\nList is Empty:\n";
        }
     else
        {
            temp=head;
	        head=head->next;
	        head->prev=NULL;
	        delete temp;
		}
}
void DLL::delete_end()
{
	a--;
	 struct node *temp,*ptr;
     if(head==NULL)
        {
    	    cout<<"\nList is Empty:\n";
	    }
	 else if(head->next==NULL)
	    {
	 	    ptr=head;
	 	    head=NULL;
	 	    delete ptr;
	    }
	 else
	    {
	 	   ptr=head;
	 	   while(ptr->next!=NULL)
	 	   {
	 	    	temp=ptr;
			   ptr=ptr->next;
	   	   }
		   temp->next=NULL;
		   delete ptr;
	    }
}
void DLL:: delete_mid()
{
	int p;
	node *temp,*ptr;
	 if(head==NULL)
        {
            cout<<"\nLinked List is Empty:\n";
        }
     cout<<"enter the position of the node to be deleted : ";
     cin>>p;
     if(p==0)
       delete_beg();
     else if(p==a-1)
       delete_end();
     else
     {
     	a--;
     	ptr=head;
     	for(int i=0;i<p;i++)
     	{
     		temp=ptr;
     		ptr=ptr->next;
     		if(ptr==NULL)
     		   cout<<"Location not found"<<endl;
		 }
		 if(ptr->next->data==p)
		 {
		 	temp=ptr->next;
		 	delete temp;
		 	ptr->next=NULL;
		 }
		 else
		 {
		    temp->next=ptr->next;
		 ptr->next->prev=temp;
		 delete ptr;
		 }

	 };
}
void DLL::search()
{
	int e,flag=0;
	node *ptr;
	ptr=head;
	cout<<"enter element to be searched : ";
	cin>>e;
	while(ptr!=NULL)
	{
		if(ptr->data==e)
		 flag=1;
		 ptr=ptr->next;
	}
	if(flag==1)
	  cout<<"Found"<<endl;
	else
	  cout<<"Not Found"<<endl;
}
void DLL::display()
{
	if(head==NULL)
        {
    	    cout<<"\nLinkedList is Empty:\n";
    	     return ;
	    }
	node *temp;
	temp=head;
	while(temp!=NULL)
	{
		cout<<temp->data<<"->";
		temp=temp->next;
	}
	cout<<endl;
}
int main()
{
	int i,n,c;
DLL obj;
    cout<<" 1  -> create node \n 2  -> Insertion at beginning \n 3  -> Insertion at any position \n 4  -> Insertion at end \n 5  -> Delete from beginning \n 6  -> Delete at any position \n 7  -> Delete from end \n 8  -> Search \n 9  -> Display \n 10 -> Exit "<<endl;
	while (1)
	{
		cout<<"enter the choice : ";
		cin>>c;
	switch(c)
	{
		case 1:
		    {
			    obj.create_node();
			    break;
	        }
		case 2:
			{
			    obj.add_beg_node();
				 break;
		    }
		case 3:
			{
		        obj.add_mid_node();
             	break;
		    }
		case 4:
			{
			     obj.add_end_node();
				 break;
		    }
		case 5:
		  	{
		    	 obj.delete_beg();
		    		break;
			}
		case 6:
		  	{
		    	 obj.delete_mid();
		    		break;
			}
		case 7:
		  	{
		    	 obj.delete_end();
		    		break;
			}
		case 8:
		  	{
		    	 obj.search();
		    		break;
			}
		case 9:
		    {
		         obj.display();
		         break;
			}
		case 10:
			{
				 cout<<endl;
	    		return 0;
			}

			}
	}
	return 0;
}
