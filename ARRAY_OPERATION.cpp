/*Write a menu driven program that implements following operations on a linear array:
1 .Insert a new element at a specified position
2.Delete an element either whose value is given or whose position is given
3.To find the location of a given element
4.To display the elements of the linear array
*/

#include<iostream>
using namespace std;
int insert_element(int arr[],int n,int index,int value)
{
  if(index>n)
  {
  cout<<"array index out of bound"<<endl;
  return n;
  }
   for (int i = n - 1; i >= index - 1; i--)
        arr[i+1] = arr[i];
   arr[index] = value;
   return (n+1);
}
int delete_element(int arr[],int n,int index)
{

  if(index>n)
  {
  cout<<"array index out of bound"<<endl;
  return n;
  }
   n--;
   for(int i=0;i<n;i++)
   {
	   if(i>=index)
	   {
		   arr[i]=arr[i+1];

	   }
   }
   return n;
}
int search_element(int arr[],int n,int ele)
{
   for(int i=0;i<n;i++)
	{
		if(arr[i]==ele)
	      return i;
	}
	return -1;
}
void display(int arr[],int n)
{
	for(int i=0;i<n;i++)
	{
		cout<<arr[i]<<" ";
	}
	cout<<endl;
}
int main()
{
	int n,ch,index,value,element,s;
	cout<<"Enter the size of array : ->";
	cin>>n;
	int *arr;
	arr=new int[n];
	cout<<"Enter elements ->"<<endl;
	for(int i=0;i<n;i++)
	  cin>>arr[i];
	cout<<"1 -> Insertion"<<endl<<"2 -> Deletion"<<endl<<"3 -> Location"<<endl<<"4 -> Display"<<endl<<"5 -> End"<<endl;
	while(1)
	{
		cout<<"Enter the choice : ";
		cin>>ch;
		switch(ch)
		{
			case 1:
				{
					cout<<"Enter the index value : -> ";
					cin>>index;
					cout<<"Enter the value : ";
					cin>>value;
					n=insert_element(arr,n,index,value);
					break;
				}
			case 2:
				{
					cout<<"Enter the index value : ->";
					cin>>index;
					n=delete_element(arr,n,index);
					break;
				}
			case 3:
				{
					cout<<"Enter the element for search : ->";
					cin>>element;
					s=search_element(arr,n,element);
					if(s>=0)
					cout<<"Element "<<element<<" is at "<<s+1<<" position"<<endl;
					else
					cout<<"ERROR ! element not found"<<endl;
					break;
				}
			case 4:
				{
					cout<<"Array element are : ->"<<endl;
					display(arr,n);
					break;
				}
			case 5:
				return 0;
		}
	}
}
