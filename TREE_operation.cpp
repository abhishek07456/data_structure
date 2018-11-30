/* Q: Write a menu driven program that implements the following operations on a Binary search tree :
	  -> Insert a new element
	  -> Delete an existing element
      -> Traversing the tree
	     - Pre-order Traversal
	     - In-order Traversal
	     - Post-order Traversal*/
#include<iostream>
using namespace std;
struct tree
{
	int data;
	tree *left;
	tree *right;
};
tree *insert(tree *node,int data)
{
	if(node==NULL)
	{
		tree *temp;
		temp=new tree;
		temp->data=data;
		temp->left=temp->right=NULL;
		return temp;
	}
	if(data<(node->data))
	{
		node->left=insert(node->left,data);
	}
	else if(data>(node->data))
	{
		node->right=insert(node->right,data);
	}
	return node;
}
tree *min(tree *node)
{
	if(node==NULL)
	{
		return NULL;
	}
	if(node->left)
	{
		return min(node->left);
	}
	else
	return node;
}
tree *delete1(tree *node,int data)
{
	if(node==NULL)
	{
		cout<<"Tree is empty"<<endl;
		return node;
	}
	else if(data<node->data)
	{
		node->left=delete1(node->left,data);
	}
	else if(data>node->data)
	{
		node->right=delete1(node->right,data);
	}
	else
	{
		tree *temp;
		temp=new tree;
		if(node->left==NULL)
		{
			temp=node->right;
			delete node;
			return temp;
		}
		else if(node->right==NULL)
		{
			temp=node->left;
		    delete node;
			return temp;
		}
		temp=min(node->right);
		node->data=temp->data;
		node->right=delete1(node->right,temp->data);
	}
	return node;
}
void inorder(tree *node)
{
	if(node==NULL)
	{
		return;
	}
	inorder(node->left);
	cout<<node->data<<" ";
	inorder(node->right);
}
void preorder(tree *node)
{
	if(node==NULL)
	{
		return;
	}
	cout<<node->data<<" ";
	preorder(node->left);
	preorder(node->right);
}
void postorder(tree *node)
{
	if(node==NULL)
	{
		return;
	}
	postorder(node->left);
	postorder(node->right);
	cout<<node->data<<" ";
}
int main()
{
	int value;
  struct tree *node=NULL;
  cout<<"1.Insert\n2.Delete\n3.Preorder traversel\n4.Inorder traversel\n5.Postorder traversel\n6.Exit\n";
  while(1)
  {
  	int ch;
  	cout<<"enter the choice : ";
  	cin>>ch;
  	switch(ch)
  	{
  		case 1:
  			cout<<"enter the value : ";
  			cin>>value;
  			node=insert(node,value);
  			break;
  		case 2:
  			cout<<"enter the value : ";
  			cin>>value;
  			node=delete1(node,value);
  			break;
  		case 3:
  			preorder(node);
  			cout<<endl;
  			break;
  		case 4:
  			inorder(node);
  			cout<<endl;
  			break;
  		case 5:
  			postorder(node);
  			cout<<endl;
  			break;
  	    case 6:
  	    	return 0;
	  }
  }
  return 0;
}

